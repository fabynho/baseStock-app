<?php

use App\Enums\DeliveryType;
use App\Enums\SaleStatus;
use App\Http\Resources\SaleResource;
use App\Models\Deliverer;
use App\Models\Sale;
use App\Models\Seller;

test('it serializes a loaded deliverer in the sale resource', function () {
    $sale = new Sale([
        'delivery_type' => DeliveryType::ScheduledDelivery,
        'status' => SaleStatus::Delivered,
    ]);
    $sale->setRelation('seller', new Seller);
    $sale->setRelation('deliverer', new Deliverer(['name' => 'João Entregador']));

    $resource = SaleResource::make($sale)->resolve();

    expect($resource['deliverer']['name'])->toBe('João Entregador');
});

test('it serializes a loaded missing deliverer as null', function () {
    $sale = new Sale([
        'delivery_type' => DeliveryType::Immediate,
        'status' => SaleStatus::Delivered,
    ]);
    $sale->setRelation('seller', new Seller);
    $sale->setRelation('deliverer', null);

    $resource = SaleResource::make($sale)->resolve();

    expect($resource['deliverer'])->toBeNull();
});
