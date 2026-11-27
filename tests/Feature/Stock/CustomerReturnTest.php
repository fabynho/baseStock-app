<?php

use App\Exceptions\StockException;
use App\Models\Floor;
use App\Models\Seller;
use App\Models\User;
use App\Services\StockService;

function returnScenario(string $deliveryType = 'immediate'): array
{
    $stock = app(StockService::class);
    $user = User::factory()->create();
    $seller = Seller::create(['name' => 'Vendedor']);
    $floor = Floor::create(['code' => 'P1', 'description' => 'Piso teste']);

    $stock->createPurchase(
        ['document_number' => 'NF-1', 'supplier_name' => 'Fornecedor'],
        [['floor_id' => $floor->id, 'quantity_m2' => '100']],
        $user,
    );

    $sale = $stock->createSale(
        ['document_number' => 'V-1', 'customer_name' => 'Cliente', 'seller_id' => $seller->id, 'delivery_type' => $deliveryType],
        [['floor_id' => $floor->id, 'quantity_m2' => '10']],
        $user,
    );

    return [$stock, $user, $floor, $sale];
}

test('devolução avulsa soma no estoque físico', function () {
    [$stock, $user, $floor] = returnScenario();

    $stock->createCustomerReturn(
        ['document_number' => 'DEV-1', 'reason' => 'Sobra de obra'],
        [['floor_id' => $floor->id, 'quantity_m2' => '4']],
        $user,
    );

    expect((float) $floor->refresh()->physical_stock)->toBe(94.0); // 100 - 10 + 4
});

test('devolução vinculada registra a venda e respeita o saldo devolvível', function () {
    [$stock, $user, $floor, $sale] = returnScenario();

    $return = $stock->createCustomerReturn(
        ['sale_id' => $sale->id, 'document_number' => 'DEV-1', 'reason' => 'Defeito'],
        [['floor_id' => $floor->id, 'quantity_m2' => '6']],
        $user,
    );

    expect($return->sale_id)->toBe($sale->id);

    // já devolveu 6 de 10: só restam 4
    expect(fn () => $stock->createCustomerReturn(
        ['sale_id' => $sale->id, 'document_number' => 'DEV-2', 'reason' => 'Defeito'],
        [['floor_id' => $floor->id, 'quantity_m2' => '5']],
        $user,
    ))->toThrow(StockException::class);

    $stock->createCustomerReturn(
        ['sale_id' => $sale->id, 'document_number' => 'DEV-2', 'reason' => 'Defeito'],
        [['floor_id' => $floor->id, 'quantity_m2' => '4']],
        $user,
    );

    expect((float) $floor->refresh()->physical_stock)->toBe(100.0);
});

test('não devolve venda que ainda não foi entregue', function () {
    [$stock, $user, $floor, $sale] = returnScenario('customer_pickup');

    expect(fn () => $stock->createCustomerReturn(
        ['sale_id' => $sale->id, 'document_number' => 'DEV-1', 'reason' => 'Teste'],
        [['floor_id' => $floor->id, 'quantity_m2' => '1']],
        $user,
    ))->toThrow(StockException::class);
});

test('não devolve piso que não consta na venda', function () {
    [$stock, $user, , $sale] = returnScenario();
    $other = Floor::create(['code' => 'P2', 'description' => 'Outro piso']);

    expect(fn () => $stock->createCustomerReturn(
        ['sale_id' => $sale->id, 'document_number' => 'DEV-1', 'reason' => 'Teste'],
        [['floor_id' => $other->id, 'quantity_m2' => '1']],
        $user,
    ))->toThrow(StockException::class);
});