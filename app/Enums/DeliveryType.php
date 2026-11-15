<?php

namespace App\Enums;

enum DeliveryType: string
{
    use \App\Enums\Concerns\HasOptions;

    case Immediate = 'immediate';
    case ScheduledDelivery = 'scheduled_delivery';
    case CustomerPickup = 'customer_pickup';

    /** Vendas não imediatas comprometem o estoque até a baixa física. */
    public function requiresReservation(): bool
    {
        return $this !== self::Immediate;
    }

    public function label(): string
    {
        return match ($this) {
            self::Immediate => 'Retirada imediata',
            self::ScheduledDelivery => 'Entrega futura',
            self::CustomerPickup => 'Retirada posterior pelo cliente',
        };
    }
}
