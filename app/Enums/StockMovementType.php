<?php

namespace App\Enums;

enum StockMovementType: string
{
    case Opening = 'opening';
    case Purchase = 'purchase';
    case PurchaseReturn = 'purchase_return';
    case Sale = 'sale';
    case SaleReturn = 'sale_return';
    case SaleCancellation = 'sale_cancellation';
    case Adjustment = 'adjustment';

    public function label(): string
    {
        return ucwords(str_replace('_', ' ', $this->value));
    }
}
