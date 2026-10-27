<?php

namespace App\Exceptions;

class InsufficientStockException extends BusinessRuleException
{
    public static function for(string $productName, string $available, string $requested): self
    {
        return new self("Insufficient stock for {$productName}: available {$available}, requested {$requested}.");
    }
}
