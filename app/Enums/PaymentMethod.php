<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Card = 'card';
    case Upi = 'upi';
    case BankTransfer = 'bank_transfer';
    case Cheque = 'cheque';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Cash',
            self::Card => 'Card',
            self::Upi => 'UPI',
            self::BankTransfer => 'Bank transfer',
            self::Cheque => 'Cheque',
        };
    }

    /** Ledger account the money lands in. */
    public function account(): string
    {
        return $this === self::Cash ? 'cash' : 'bank';
    }
}
