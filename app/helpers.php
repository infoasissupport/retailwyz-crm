<?php

use App\Services\SettingsService;

if (! function_exists('money')) {
    /**
     * Display formatting only. The value is already rounded server-side;
     * number_format here never feeds back into a calculation.
     */
    function money(string|int|float|null $amount, bool $symbol = true): string
    {
        $formatted = number_format((float) ($amount ?? 0), 2);

        return $symbol ? app(SettingsService::class)->get('currency_symbol', '').$formatted : $formatted;
    }
}

if (! function_exists('qty')) {
    /** Display a decimal quantity without trailing zeros (5.000 -> 5, 2.500 -> 2.5). */
    function qty(string|int|float|null $quantity): string
    {
        $value = (string) ($quantity ?? '0');

        return str_contains($value, '.') ? rtrim(rtrim($value, '0'), '.') : $value;
    }
}
