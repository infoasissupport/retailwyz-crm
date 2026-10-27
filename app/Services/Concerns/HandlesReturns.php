<?php

namespace App\Services\Concerns;

use App\Exceptions\BusinessRuleException;
use App\Support\LineCalculator;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

trait HandlesReturns
{
    /**
     * Combine duplicate submitted lines for the same source item and drop zero quantities.
     *
     * @return array<int, string> source item id => quantity
     */
    protected function mergeReturnLines(array $lines, string $key): array
    {
        $merged = [];
        foreach ($lines as $line) {
            if (Money::cmp($line['quantity'] ?? '0', '0') <= 0) {
                continue;
            }
            $merged[(int) $line[$key]] = Money::add($merged[(int) $line[$key]] ?? '0', $line['quantity']);
        }

        if ($merged === []) {
            throw new BusinessRuleException('Enter a quantity for at least one item.');
        }

        return $merged;
    }

    /**
     * Amount and tax credited for returning part of a line. The return that
     * completes the whole line takes whatever remains, so rounding never
     * leaves a residue.
     *
     * @return array{0: string, 1: string} [line total, tax]
     */
    protected function returnShare(Model $item, string $quantity, string $returnItemsTable, string $foreignKey): array
    {
        if (Money::cmp(Money::add($item->returned_quantity, $quantity), $item->quantity) === 0) {
            $previous = DB::table($returnItemsTable)
                ->where($foreignKey, $item->id)
                ->selectRaw('COALESCE(SUM(line_total), 0) as total, COALESCE(SUM(tax_amount), 0) as tax')
                ->first();

            return [
                Money::round(Money::sub($item->line_total, $previous->total)),
                Money::round(Money::sub($item->tax_amount, $previous->tax)),
            ];
        }

        return [
            LineCalculator::prorate($item->line_total, $quantity, $item->quantity),
            LineCalculator::prorate($item->tax_amount, $quantity, $item->quantity),
        ];
    }
}
