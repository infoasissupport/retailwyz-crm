<?php

namespace App\Support;

use App\Exceptions\BusinessRuleException;

/**
 * Authoritative line-level pricing for sales and purchases.
 *
 * Tax-exclusive: net = qty * price - discount, tax = net * rate%, total = net + tax.
 * Tax-inclusive: total = qty * price - discount, tax = total * rate / (100 + rate), net = total - tax.
 */
final class LineCalculator
{
    /**
     * @return array{gross: string, discount: string, net: string, tax: string, total: string}
     */
    public static function calculate(
        string|int|float $quantity,
        string|int|float $unitPrice,
        string|int|float|null $discount,
        string|int|float|null $taxRate,
        bool $pricesIncludeTax,
    ): array {
        $gross = Money::round(Money::mul($quantity, $unitPrice));
        $discount = Money::round($discount ?? '0');
        $afterDiscount = Money::sub($gross, $discount);

        if (Money::cmp($discount, '0') < 0 || Money::cmp($afterDiscount, '0') < 0) {
            throw new BusinessRuleException('Line discount must be between zero and the line amount.');
        }

        $rate = Money::normalize($taxRate ?? '0');

        if ($pricesIncludeTax) {
            $total = Money::round($afterDiscount);
            $tax = Money::round(Money::div(Money::mul($total, $rate), Money::add('100', $rate)));
            $net = Money::round(Money::sub($total, $tax));
        } else {
            $net = Money::round($afterDiscount);
            $tax = Money::round(Money::percent($net, $rate));
            $total = Money::round(Money::add($net, $tax));
        }

        return compact('gross', 'discount', 'net', 'tax', 'total');
    }

    /**
     * Pro-rata share of a stored amount for a partial quantity (returns).
     */
    public static function prorate(string $amount, string $partQuantity, string $fullQuantity): string
    {
        return Money::round(Money::div(Money::mul($amount, $partQuantity), $fullQuantity));
    }
}
