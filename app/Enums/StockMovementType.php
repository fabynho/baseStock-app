<?php

namespace App\Enums;

enum StockMovementType: string
{
    use \App\Enums\Concerns\HasOptions;

    case PurchaseIn = 'purchase_in';
    case CustomerReturnIn = 'customer_return_in';
    case SaleReservation = 'sale_reservation';
    case SaleDeliveryOut = 'sale_delivery_out';
    case SaleCancelRelease = 'sale_cancel_release';
    case BreakageOut = 'breakage_out';
    case ManualAdjustment = 'manual_adjustment';

    public function label(): string
    {
        return match ($this) {
            self::PurchaseIn => 'Entrada por compra',
            self::CustomerReturnIn => 'Devolução de cliente',
            self::SaleReservation => 'Reserva por venda',
            self::SaleDeliveryOut => 'Baixa por entrega/retirada',
            self::SaleCancelRelease => 'Liberação por cancelamento',
            self::BreakageOut => 'Baixa por quebra/avaria',
            self::ManualAdjustment => 'Ajuste manual',
        };
    }
}
