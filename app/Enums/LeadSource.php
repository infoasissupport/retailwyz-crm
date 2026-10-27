<?php

namespace App\Enums;

enum LeadSource: string
{
    case WalkIn = 'walk_in';
    case Phone = 'phone';
    case WhatsApp = 'whatsapp';
    case Website = 'website';
    case SocialMedia = 'social_media';
    case Referral = 'referral';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::WalkIn => 'Walk-in',
            self::WhatsApp => 'WhatsApp',
            self::SocialMedia => 'Social media',
            default => ucfirst($this->value),
        };
    }
}
