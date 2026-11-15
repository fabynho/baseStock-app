<?php

namespace App\Models;

use App\Enums\StockMovementType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Kardex append-only: registros nunca são alterados nem excluídos.
 */
class StockMovement extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'floor_id',
        'user_id',
        'type',
        'quantity_m2',
        'physical_balance_after',
        'committed_balance_after',
        'document_number',
        'sale_id',
        'purchase_id',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'type' => StockMovementType::class,
            'quantity_m2' => 'decimal:4',
            'physical_balance_after' => 'decimal:4',
            'committed_balance_after' => 'decimal:4',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Movimentações de estoque são imutáveis.'));
        static::deleting(fn () => throw new LogicException('Movimentações de estoque não podem ser excluídas.'));
    }

    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    // ---- Filtros do Kardex ----

    public function scopeForFloor(Builder $query, ?int $floorId): Builder
    {
        return $floorId ? $query->where('floor_id', $floorId) : $query;
    }

    public function scopeOfType(Builder $query, ?StockMovementType $type): Builder
    {
        return $type ? $query->where('type', $type->value) : $query;
    }

    public function scopeForDocument(Builder $query, ?string $document): Builder
    {
        return filled($document) ? $query->where('document_number', $document) : $query;
    }

    public function scopeBetween(Builder $query, $from, $to): Builder
    {
        return $query
            ->when($from, fn (Builder $q) => $q->where('created_at', '>=', $from))
            ->when($to, fn (Builder $q) => $q->where('created_at', '<=', $to));
    }
}
