<?php

namespace App\Enums;

enum PurchaseStatus: string
{
    case Draft = 'draft';
    case Ordered = 'ordered';
    case PartiallyReceived = 'partial';
    case Received = 'received';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PartiallyReceived => 'Partially received',
            default => ucfirst($this->value),
        };
    }

    public function isEditable(): bool
    {
        return in_array($this, [self::Draft, self::Ordered], true);
    }

    public function canReceive(): bool
    {
        return in_array($this, [self::Ordered, self::PartiallyReceived], true);
    }
}
