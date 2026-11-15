<?php

namespace App\Services;

use App\Enums\DeliveryType;
use App\Enums\SaleStatus;
use App\Enums\StockMovementType;
use App\Exceptions\StockException;
use App\Models\Deliverer;
use App\Models\Floor;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\User;
use Brick\Math\BigDecimal;
use Brick\Math\Exception\MathException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Único ponto de alteração de saldos de estoque.
 *
 * Regras:
 *  - Toda operação roda em DB::transaction() com lockForUpdate() nos pisos envolvidos
 *    (travados em ordem crescente de id para evitar deadlock entre vendas concorrentes).
 *  - Aritmética em BigDecimal (escala 4); nunca float.
 *  - Toda alteração de saldo gera exatamente um StockMovement por piso.
 *  - Invariantes garantidas: 0 <= committed_stock <= physical_stock.
 *
 * Quantidades devem ser passadas como string ("12.5000") ou int. Evite float.
 */
class StockService
{
    private const SCALE = 4;

    // =====================================================================
    // Fluxo A — Entradas (compras e devoluções)
    // =====================================================================

    /**
     * Entrada por compra de fornecedor (cabeçalho + itens), no mesmo molde de createSale().
     * Soma cada item em physical_stock e gera um purchase_in por piso.
     *
     * @param array{
     *     document_number: string,
     *     supplier_name: string,
     *     document_date?: \DateTimeInterface|string|null,
     *     received_at?: \DateTimeInterface|string|null,
     *     notes?: ?string,
     * } $data
     * @param array<int, array{floor_id: int, quantity_m2: string|int|float}> $items
     */
    public function createPurchase(array $data, array $items, User $user): Purchase
    {
        $this->requireFilled($data['document_number'] ?? null, 'Número do documento');
        $this->requireFilled($data['supplier_name'] ?? null, 'Fornecedor');

        $lines = $this->consolidateItems($items);

        return DB::transaction(function () use ($data, $lines, $user) {
            $floors = $this->lockFloors(array_keys($lines));

            foreach ($lines as $floorId => $qty) {
                if (! $floors[$floorId]->is_active) {
                    throw StockException::inactiveFloor($floors[$floorId]);
                }
            }

            $purchase = Purchase::create([
                'document_number' => trim($data['document_number']),
                'supplier_name' => trim($data['supplier_name']),
                'document_date' => $data['document_date'] ?? null,
                'received_at' => $data['received_at'] ?? now(),
                'notes' => $data['notes'] ?? null,
                'created_by' => $user->id,
            ]);

            foreach ($lines as $floorId => $qty) {
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'floor_id' => $floorId,
                    'quantity_m2' => (string) $qty,
                ]);

                $this->apply(
                    $floors[$floorId],
                    StockMovementType::PurchaseIn,
                    $qty,
                    physicalDelta: $qty,
                    committedDelta: $this->zero(),
                    user: $user,
                    documentNumber: $purchase->document_number,
                    purchaseId: $purchase->id,
                );
            }

            return $purchase->load('items.floor');
        });
    }

    public function registerCustomerReturn(
        Floor|int $floor,
        string|int|float $quantityM2,
        User $user,
        string $documentNumber,
        string $reason,
    ): StockMovement {
        $this->requireFilled($documentNumber, 'Número do documento');
        $this->requireFilled($reason, 'Motivo');
        $qty = $this->positive($quantityM2);

        return DB::transaction(fn () => $this->apply(
            $this->lockFloor($floor),
            StockMovementType::CustomerReturnIn,
            $qty,
            physicalDelta: $qty,
            committedDelta: $this->zero(),
            user: $user,
            documentNumber: $documentNumber,
            reason: $reason,
        ));
    }

    // =====================================================================
    // Fluxo C — Quebras e avarias
    // =====================================================================

    public function registerBreakage(
        Floor|int $floor,
        string|int|float $quantityM2,
        User $user,
        string $reason,
    ): StockMovement {
        $this->requireFilled($reason, 'Motivo');
        $qty = $this->positive($quantityM2);

        return DB::transaction(function () use ($floor, $qty, $user, $reason) {
            $locked = $this->lockFloor($floor);
            $this->assertAvailable($locked, $qty);

            return $this->apply(
                $locked,
                StockMovementType::BreakageOut,
                $qty,
                physicalDelta: $qty->negated(),
                committedDelta: $this->zero(),
                user: $user,
                reason: $reason,
            );
        });
    }

    // =====================================================================
    // Ajuste manual (somente admin)
    // =====================================================================

    /**
     * @param string|int|float $deltaM2 Variação com sinal sobre o estoque físico (ex.: "-3.5" ou "10").
     *                                  Gravada com sinal em stock_movements.quantity_m2.
     */
    public function adjustManually(
        Floor|int $floor,
        string|int|float $deltaM2,
        User $user,
        string $reason,
    ): StockMovement {
        $this->assertAdmin($user);
        $this->requireFilled($reason, 'Motivo');

        $delta = $this->decimal($deltaM2);
        if ($delta->isZero()) {
            throw StockException::zeroAdjustment();
        }

        return DB::transaction(fn () => $this->apply(
            $this->lockFloor($floor),
            StockMovementType::ManualAdjustment,
            $delta,
            physicalDelta: $delta,
            committedDelta: $this->zero(),
            user: $user,
            reason: $reason,
        ));
    }

    // =====================================================================
    // Fluxo B — Vendas e entregas
    // =====================================================================

    /**
     * @param array{
     *     document_number: string,
     *     customer_name: string,
     *     seller_id: int,
     *     delivery_type: DeliveryType|string,
     *     sold_at?: \DateTimeInterface|string|null,
     *     notes?: ?string,
     * } $data
     * @param array<int, array{floor_id: int, quantity_m2: string|int|float}> $items
     */
    public function createSale(array $data, array $items, User $user): Sale
    {
        $type = $data['delivery_type'] instanceof DeliveryType
            ? $data['delivery_type']
            : DeliveryType::from($data['delivery_type']);

        $lines = $this->consolidateItems($items);

        return DB::transaction(function () use ($data, $type, $lines, $user) {
            $floors = $this->lockFloors(array_keys($lines));

            // Valida TODOS os itens antes de gravar qualquer coisa.
            foreach ($lines as $floorId => $qty) {
                $floor = $floors[$floorId];

                if (! $floor->is_active) {
                    throw StockException::inactiveFloor($floor);
                }
                $this->assertAvailable($floor, $qty);
            }

            $immediate = ! $type->requiresReservation();

            $sale = Sale::create([
                'document_number' => $data['document_number'],
                'customer_name' => $data['customer_name'],
                'seller_id' => $data['seller_id'],
                'deliverer_id' => null, // definido na confirmação da entrega
                'delivery_type' => $type,
                'status' => $immediate ? SaleStatus::Delivered : SaleStatus::PendingDelivery,
                'sold_at' => $data['sold_at'] ?? now(),
                'delivered_at' => $immediate ? now() : null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $user->id,
            ]);

            foreach ($lines as $floorId => $qty) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'floor_id' => $floorId,
                    'quantity_m2' => (string) $qty,
                ]);

                if ($immediate) {
                    // Cenário 1: baixa direta do físico.
                    $this->apply(
                        $floors[$floorId],
                        StockMovementType::SaleDeliveryOut,
                        $qty,
                        physicalDelta: $qty->negated(),
                        committedDelta: $this->zero(),
                        user: $user,
                        documentNumber: $sale->document_number,
                        saleId: $sale->id,
                    );
                } else {
                    // Cenário 2 / etapa 1: apenas compromete.
                    $this->apply(
                        $floors[$floorId],
                        StockMovementType::SaleReservation,
                        $qty,
                        physicalDelta: $this->zero(),
                        committedDelta: $qty,
                        user: $user,
                        documentNumber: $sale->document_number,
                        saleId: $sale->id,
                    );
                }
            }

            return $sale->load('items.floor');
        });
    }

    /**
     * Cenário 2 / etapa 2: baixa física de venda pendente.
     * Entrega futura (scheduled_delivery) exige entregador; retirada pelo cliente não.
     */
    public function confirmDelivery(Sale|int $sale, User $user, ?int $delivererId = null): Sale
    {
        return DB::transaction(function () use ($sale, $user, $delivererId) {
            $locked = $this->lockSale($sale);

            if (! $locked->isPending()) {
                throw StockException::saleNotPending($locked);
            }

            if ($locked->delivery_type === DeliveryType::ScheduledDelivery && ! $delivererId) {
                throw StockException::delivererRequired();
            }
            if ($delivererId && ! Deliverer::query()->whereKey($delivererId)->where('is_active', true)->exists()) {
                throw StockException::delivererInvalid();
            }

            $items = $locked->items()->orderBy('floor_id')->get();
            $floors = $this->lockFloors($items->pluck('floor_id')->all());

            foreach ($items as $item) {
                $qty = $this->decimal($item->quantity_m2);

                $this->apply(
                    $floors[$item->floor_id],
                    StockMovementType::SaleDeliveryOut,
                    $qty,
                    physicalDelta: $qty->negated(),
                    committedDelta: $qty->negated(),
                    user: $user,
                    documentNumber: $locked->document_number,
                    saleId: $locked->id,
                );
            }

            $locked->forceFill([
                'status' => SaleStatus::Delivered,
                'delivered_at' => now(),
                'deliverer_id' => $delivererId,
            ])->save();

            return $locked->load('items.floor', 'deliverer');
        });
    }

    /**
     * Cancela venda PENDENTE e libera o estoque comprometido. Somente admin.
     * Venda já entregue não é cancelável aqui: use registerCustomerReturn().
     */
    public function cancelSale(Sale|int $sale, User $user, string $reason): Sale
    {
        $this->assertAdmin($user);
        $this->requireFilled($reason, 'Motivo do cancelamento');

        return DB::transaction(function () use ($sale, $user, $reason) {
            $locked = $this->lockSale($sale);

            if (! $locked->isPending()) {
                throw StockException::saleNotPending($locked);
            }

            $items = $locked->items()->orderBy('floor_id')->get();
            $floors = $this->lockFloors($items->pluck('floor_id')->all());

            foreach ($items as $item) {
                $qty = $this->decimal($item->quantity_m2);

                $this->apply(
                    $floors[$item->floor_id],
                    StockMovementType::SaleCancelRelease,
                    $qty,
                    physicalDelta: $this->zero(),
                    committedDelta: $qty->negated(),
                    user: $user,
                    documentNumber: $locked->document_number,
                    saleId: $locked->id,
                    reason: $reason,
                );
            }

            $locked->forceFill([
                'status' => SaleStatus::Canceled,
                'notes' => trim(($locked->notes ? $locked->notes."\n" : '').'[Cancelada] '.$reason),
            ])->save();

            return $locked->load('items.floor');
        });
    }

    // =====================================================================
    // Núcleo: aplica deltas, valida invariantes, grava piso + kardex
    // =====================================================================

    /**
     * Deve ser chamado DENTRO de transação, com $floor já travado (lockForUpdate).
     */
    private function apply(
        Floor $floor,
        StockMovementType $type,
        BigDecimal $quantity,
        BigDecimal $physicalDelta,
        BigDecimal $committedDelta,
        User $user,
        ?string $documentNumber = null,
        ?int $saleId = null,
        ?string $reason = null,
        ?int $purchaseId = null,
    ): StockMovement {
        $physical = $this->decimal($floor->physical_stock)->plus($physicalDelta)->toScale(self::SCALE);
        $committed = $this->decimal($floor->committed_stock)->plus($committedDelta)->toScale(self::SCALE);

        if ($physical->isNegative()) {
            throw StockException::negativePhysical($floor);
        }
        if ($committed->isNegative()) {
            throw StockException::negativeCommitted($floor);
        }
        if ($committed->isGreaterThan($physical)) {
            throw StockException::belowCommitted($floor);
        }

        $floor->forceFill([
            'physical_stock' => (string) $physical,
            'committed_stock' => (string) $committed,
        ])->save();

        return StockMovement::create([
            'floor_id' => $floor->id,
            'user_id' => $user->id,
            'type' => $type,
            'quantity_m2' => (string) $quantity,
            'physical_balance_after' => (string) $physical,
            'committed_balance_after' => (string) $committed,
            'document_number' => filled($documentNumber) ? trim($documentNumber) : null,
            'sale_id' => $saleId,
            'purchase_id' => $purchaseId,
            'reason' => filled($reason) ? trim($reason) : null,
        ]);
    }

    // =====================================================================
    // Helpers
    // =====================================================================

    private function lockFloor(Floor|int $floor): Floor
    {
        return Floor::query()
            ->whereKey($floor instanceof Floor ? $floor->id : $floor)
            ->lockForUpdate()
            ->firstOr(fn () => throw StockException::floorNotFound());
    }

    /**
     * Trava vários pisos em ordem crescente de id (evita deadlock).
     *
     * @param  array<int, int>  $ids
     * @return Collection<int, Floor>  indexado por id
     */
    private function lockFloors(array $ids): Collection
    {
        $ids = array_values(array_unique($ids));

        $floors = Floor::query()
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        if ($floors->count() !== count($ids)) {
            throw StockException::floorNotFound();
        }

        return $floors;
    }

    private function lockSale(Sale|int $sale): Sale
    {
        return Sale::query()
            ->whereKey($sale instanceof Sale ? $sale->id : $sale)
            ->lockForUpdate()
            ->firstOrFail();
    }

    /** quantity <= physical - committed */
    private function assertAvailable(Floor $floor, BigDecimal $qty): void
    {
        $available = $this->decimal($floor->physical_stock)->minus($this->decimal($floor->committed_stock));

        if ($qty->isGreaterThan($available)) {
            throw StockException::insufficientStock($floor, $qty, $available);
        }
    }

    /**
     * Soma itens repetidos do mesmo piso e ordena por floor_id.
     *
     * @return array<int, BigDecimal>  [floor_id => quantidade]
     */
    private function consolidateItems(array $items): array
    {
        $lines = [];

        foreach ($items as $item) {
            $floorId = (int) ($item['floor_id'] ?? 0);
            if ($floorId <= 0) {
                throw StockException::floorNotFound();
            }

            $qty = $this->positive($item['quantity_m2'] ?? 0);
            $lines[$floorId] = isset($lines[$floorId]) ? $lines[$floorId]->plus($qty) : $qty;
        }

        if ($lines === []) {
            throw StockException::emptySale();
        }

        ksort($lines);

        return $lines;
    }

    private function decimal(string|int|float $value): BigDecimal
    {
        try {
            return BigDecimal::of((string) $value)->toScale(self::SCALE);
        } catch (MathException) {
            // formato inválido ou mais de 4 casas decimais
            throw StockException::invalidQuantity();
        }
    }

    private function positive(string|int|float $value): BigDecimal
    {
        $decimal = $this->decimal($value);

        if (! $decimal->isPositive()) {
            throw StockException::invalidQuantity();
        }

        return $decimal;
    }

    private function zero(): BigDecimal
    {
        return BigDecimal::zero()->toScale(self::SCALE);
    }

    private function requireFilled(?string $value, string $label): void
    {
        if (blank($value)) {
            throw StockException::missingField($label);
        }
    }

    private function assertAdmin(User $user): void
    {
        if (! $user->isAdmin()) {
            throw new AuthorizationException('Apenas administradores podem executar esta operação.');
        }
    }
}
