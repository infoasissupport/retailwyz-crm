<?php

namespace App\Enums;

enum DeliveryStatus: string
{
    case Pending = 'pending';
    case OutForDelivery = 'out_for_delivery';
    case Delivered = 'delivered';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::OutForDelivery => 'Out for delivery',
            default => ucfirst($this->value),
        };
    }

    /** Details can still be changed (address, date, driver). */
    public function isEditable(): bool
    {
        return in_array($this, [self::Pending, self::Failed], true);
    }
}
