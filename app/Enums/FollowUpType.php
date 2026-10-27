<?php

namespace App\Enums;

enum FollowUpType: string
{
    case Call = 'call';
    case Visit = 'visit';
    case WhatsApp = 'whatsapp';
    case Email = 'email';
    case Meeting = 'meeting';

    public function label(): string
    {
        return $this === self::WhatsApp ? 'WhatsApp' : ucfirst($this->value);
    }
}
