<?php

namespace App\Enums;

enum ProductStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Discontinued = 'discontinued';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
