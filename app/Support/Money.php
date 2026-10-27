<?php

namespace App\Support;

/**
 * Decimal-safe arithmetic for monetary values and quantities.
 *
 * All values are handled as numeric strings via bcmath; floats are never used
 * for money. Intermediate results keep extra precision and are rounded
 * half-up only when explicitly requested.
 */
final class Money
{
    private const SCALE = 6;

    public static function add(string|int|float|null ...$values): string
    {
        $total = '0';
        foreach ($values as $value) {
            $total = bcadd($total, self::normalize($value), self::SCALE);
        }

        return $total;
    }

    public static function sub(string|int|float|null $a, string|int|float|null $b): string
    {
        return bcsub(self::normalize($a), self::normalize($b), self::SCALE);
    }

    public static function mul(string|int|float|null $a, string|int|float|null $b): string
    {
        return bcmul(self::normalize($a), self::normalize($b), self::SCALE);
    }

    public static function div(string|int|float|null $a, string|int|float|null $b): string
    {
        $divisor = self::normalize($b);
        if (bccomp($divisor, '0', self::SCALE) === 0) {
            throw new \DivisionByZeroError('Division by zero in Money::div');
        }

        return bcdiv(self::normalize($a), $divisor, self::SCALE);
    }

    /** Percentage of an amount: $amount * $rate / 100. */
    public static function percent(string|int|float|null $amount, string|int|float|null $rate): string
    {
        return self::div(self::mul($amount, $rate), '100');
    }

    /** Round half away from zero to the given precision. */
    public static function round(string|int|float|null $value, int $precision = 2): string
    {
        $value = self::normalize($value);
        $offset = '0.'.str_repeat('0', $precision).'5';
        $offset = bccomp($value, '0', self::SCALE) < 0 ? '-'.$offset : $offset;

        return bcadd($value, $offset, $precision);
    }

    public static function cmp(string|int|float|null $a, string|int|float|null $b): int
    {
        return bccomp(self::normalize($a), self::normalize($b), self::SCALE);
    }

    public static function isZero(string|int|float|null $value): bool
    {
        return self::cmp($value, '0') === 0;
    }

    public static function max(string|int|float|null $a, string|int|float|null $b): string
    {
        return self::cmp($a, $b) >= 0 ? self::normalize($a) : self::normalize($b);
    }

    public static function normalize(string|int|float|null $value): string
    {
        if ($value === null || $value === '') {
            return '0';
        }

        if (is_float($value)) {
            // Floats only arrive from loosely-typed input; format without exponent.
            $value = number_format($value, self::SCALE, '.', '');
        }

        $value = trim((string) $value);

        if (! is_numeric($value)) {
            throw new \InvalidArgumentException("Invalid numeric value [{$value}]");
        }

        return $value;
    }
}
