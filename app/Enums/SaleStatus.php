<?php

namespace App\Enums;

enum SaleStatus: string
{
    use \App\Enums\Concerns\HasOptions;

    case PendingDelivery = 'pending_delivery';
    case Delivered = 'delivered';
    case Canceled = 'canceled';

    public function label(): string
    {
        return match ($this) {
            self::PendingDelivery => 'Pendente de retirada/entrega',
            self::Delivered => 'Entregue',
            self::Canceled => 'Cancelada',
        };
    }
}
