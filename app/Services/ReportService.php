<?php

namespace App\Services;

use App\Enums\PurchaseStatus;
use App\Enums\SaleStatus;
use App\Support\Money;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Read-only report queries. Everything is aggregated in the database; PHP
 * only rounds and subtracts the aggregated strings via Money.
 *
 * $branchId null = all branches (callers pass the user's branch when restricted).
 */
class ReportService
{
    public function sales(string $from, string $to, ?int $branchId): array
    {
        $sales = $this->completedSales($from, $to, $branchId);

        $totals = (clone $sales)->selectRaw('COUNT(*) as invoices, COALESCE(SUM(subtotal),0) as gross, COALESCE(SUM(discount_total),0) as discounts,
            COALESCE(SUM(tax_total),0) as tax, COALESCE(SUM(grand_total),0) as total')->first();

        $returns = $this->saleReturns($from, $to, $branchId)
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(grand_total),0) as total, COALESCE(SUM(tax_total),0) as tax')->first();

        return [
            'totals' => $this->rounded($totals) + [
                'returns' => Money::round($returns->total),
                'returns_count' => (int) $returns->count,
                'net' => Money::round(Money::sub($totals->total, $returns->total)),
            ],
            'byDay' => (clone $sales)->selectRaw('sale_date as day, COUNT(*) as invoices, SUM(grand_total) as total, SUM(tax_total) as tax')
                ->groupBy('sale_date')->orderBy('sale_date')->get(),
            'byCategory' => DB::table('sale_items')
                ->joinSub($sales->select('id'), 's', 's.id', '=', 'sale_items.sale_id')
                ->join('products', 'products.id', '=', 'sale_items.product_id')
                ->join('categories', 'categories.id', '=', 'products.category_id')
                ->selectRaw('categories.name as category, SUM(sale_items.quantity) as quantity, SUM(sale_items.line_total - sale_items.tax_amount) as net, SUM(sale_items.line_total) as total')
                ->groupBy('categories.id', 'categories.name')
                ->orderByDesc('total')
                ->get(),
        ];
    }

    public function purchases(string $from, string $to, ?int $branchId): array
    {
        $purchases = DB::table('purchases')
            ->whereNotIn('status', [PurchaseStatus::Draft->value, PurchaseStatus::Cancelled->value])
            ->whereBetween('purchase_date', [$from, $to])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId));

        $totals = (clone $purchases)->selectRaw('COUNT(*) as orders, COALESCE(SUM(subtotal),0) as gross, COALESCE(SUM(discount_total),0) as discounts,
            COALESCE(SUM(tax_total),0) as tax, COALESCE(SUM(shipping_cost),0) as shipping, COALESCE(SUM(grand_total),0) as total,
            COALESCE(SUM(paid_amount),0) as paid')->first();

        $returns = DB::table('purchase_returns')
            ->whereBetween('return_date', [$from, $to])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(grand_total),0) as total')->first();

        return [
            'totals' => $this->rounded($totals) + [
                'returns' => Money::round($returns->total),
                'returns_count' => (int) $returns->count,
                'net' => Money::round(Money::sub($totals->total, $returns->total)),
            ],
            'bySupplier' => (clone $purchases)
                ->join('suppliers', 'suppliers.id', '=', 'purchases.supplier_id')
                ->selectRaw('suppliers.name as supplier, COUNT(*) as orders, SUM(purchases.grand_total) as total, SUM(purchases.paid_amount) as paid')
                ->groupBy('suppliers.id', 'suppliers.name')
                ->orderByDesc('total')
                ->get(),
        ];
    }

    /** Stock on hand valued at current cost price. */
    public function stockValuation(?int $branchId): Builder
    {
        return DB::table('stock_levels')
            ->join('products', 'products.id', '=', 'stock_levels.product_id')
            ->join('branches', 'branches.id', '=', 'stock_levels.branch_id')
            ->leftJoin('product_variants', 'product_variants.id', '=', 'stock_levels.product_variant_id')
            ->whereNull('products.deleted_at')
            ->when($branchId, fn ($q) => $q->where('stock_levels.branch_id', $branchId))
            ->selectRaw('products.id as product_id, products.sku, products.name, product_variants.name as variant, branches.name as branch,
                stock_levels.quantity, COALESCE(product_variants.cost_price, products.cost_price) as cost_price,
                stock_levels.quantity * COALESCE(product_variants.cost_price, products.cost_price) as value')
            ->orderBy('products.name');
    }

    public function stockValueTotal(?int $branchId): string
    {
        return Money::round(DB::query()->fromSub($this->stockValuation($branchId), 'v')->sum('value'));
    }

    /** Tracked products whose total on-hand quantity is at or below their minimum. */
    public function lowStock(?int $branchId): Collection
    {
        $stock = DB::table('stock_levels')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->selectRaw('product_id, SUM(quantity) as quantity')
            ->groupBy('product_id');

        return DB::table('products')
            ->leftJoinSub($stock, 'st', 'st.product_id', '=', 'products.id')
            ->whereNull('products.deleted_at')
            ->where('products.track_stock', true)
            ->where('products.status', 'active')
            ->whereRaw('COALESCE(st.quantity, 0) <= products.min_stock')
            ->selectRaw('products.id, products.sku, products.name, products.min_stock, COALESCE(st.quantity, 0) as quantity')
            ->orderBy('products.name')
            ->get();
    }

    public function profitAndLoss(string $from, string $to, ?int $branchId): array
    {
        $sales = $this->completedSales($from, $to, $branchId)->select('id');

        $sold = DB::table('sale_items')
            ->joinSub($sales, 's', 's.id', '=', 'sale_items.sale_id')
            ->selectRaw('COALESCE(SUM(line_total - tax_amount),0) as revenue, COALESCE(SUM(quantity * unit_cost),0) as cogs')
            ->first();

        $returned = DB::table('sale_return_items')
            ->joinSub($this->saleReturns($from, $to, $branchId)->select('id'), 'r', 'r.id', '=', 'sale_return_items.sale_return_id')
            ->join('sale_items', 'sale_items.id', '=', 'sale_return_items.sale_item_id')
            ->selectRaw('COALESCE(SUM(sale_return_items.line_total - sale_return_items.tax_amount),0) as revenue,
                COALESCE(SUM(sale_return_items.quantity * sale_items.unit_cost),0) as cogs')
            ->first();

        $expenses = DB::table('expenses')
            ->join('expense_categories', 'expense_categories.id', '=', 'expenses.expense_category_id')
            ->whereNull('expenses.deleted_at')
            ->whereBetween('expense_date', [$from, $to])
            ->when($branchId, fn ($q) => $q->where('expenses.branch_id', $branchId))
            ->selectRaw('expense_categories.name as category, SUM(expenses.amount - expenses.tax_amount) as total')
            ->groupBy('expense_categories.id', 'expense_categories.name')
            ->orderBy('expense_categories.name')
            ->get();

        $revenue = Money::round(Money::sub($sold->revenue, $returned->revenue));
        $cogs = Money::round(Money::sub($sold->cogs, $returned->cogs));
        $grossProfit = Money::round(Money::sub($revenue, $cogs));
        $expenseTotal = Money::round(Money::add(...$expenses->pluck('total')->all()));

        return [
            'sales_revenue' => Money::round($sold->revenue),
            'returns_revenue' => Money::round($returned->revenue),
            'revenue' => $revenue,
            'cogs' => $cogs,
            'gross_profit' => $grossProfit,
            'expenses' => $expenses,
            'expense_total' => $expenseTotal,
            'net_profit' => Money::round(Money::sub($grossProfit, $expenseTotal)),
        ];
    }

    public function tax(string $from, string $to, ?int $branchId): array
    {
        $sales = $this->completedSales($from, $to, $branchId)->select('id');
        $outputSold = DB::table('sale_items')
            ->joinSub($sales, 's', 's.id', '=', 'sale_items.sale_id')
            ->selectRaw('tax_rate, SUM(line_total - tax_amount) as taxable, SUM(tax_amount) as tax')
            ->groupBy('tax_rate')->get()->keyBy(fn ($r) => (string) Money::round($r->tax_rate, 3));

        $outputReturned = DB::table('sale_return_items')
            ->joinSub($this->saleReturns($from, $to, $branchId)->select('id'), 'r', 'r.id', '=', 'sale_return_items.sale_return_id')
            ->join('sale_items', 'sale_items.id', '=', 'sale_return_items.sale_item_id')
            ->selectRaw('sale_items.tax_rate, SUM(sale_return_items.line_total - sale_return_items.tax_amount) as taxable, SUM(sale_return_items.tax_amount) as tax')
            ->groupBy('sale_items.tax_rate')->get()->keyBy(fn ($r) => (string) Money::round($r->tax_rate, 3));

        // Input tax only on goods actually received, pro-rata for partial receipts.
        $purchases = DB::table('purchases')
            ->whereNotIn('status', [PurchaseStatus::Draft->value, PurchaseStatus::Cancelled->value])
            ->whereBetween('purchase_date', [$from, $to])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->select('id');
        $inputBought = DB::table('purchase_items')
            ->joinSub($purchases, 'p', 'p.id', '=', 'purchase_items.purchase_id')
            ->selectRaw('tax_rate, SUM((line_total - tax_amount) * received_quantity / quantity) as taxable, SUM(tax_amount * received_quantity / quantity) as tax')
            ->groupBy('tax_rate')->get()->keyBy(fn ($r) => (string) Money::round($r->tax_rate, 3));

        $inputReturned = DB::table('purchase_return_items')
            ->join('purchase_returns', 'purchase_returns.id', '=', 'purchase_return_items.purchase_return_id')
            ->join('purchase_items', 'purchase_items.id', '=', 'purchase_return_items.purchase_item_id')
            ->whereBetween('purchase_returns.return_date', [$from, $to])
            ->when($branchId, fn ($q) => $q->where('purchase_returns.branch_id', $branchId))
            ->selectRaw('purchase_items.tax_rate, SUM(purchase_return_items.line_total - purchase_return_items.tax_amount) as taxable, SUM(purchase_return_items.tax_amount) as tax')
            ->groupBy('purchase_items.tax_rate')->get()->keyBy(fn ($r) => (string) Money::round($r->tax_rate, 3));

        $rates = collect([$outputSold, $outputReturned, $inputBought, $inputReturned])
            ->flatMap(fn ($c) => $c->keys())->unique()->sort(fn ($a, $b) => Money::cmp($a, $b))->values();

        $rows = $rates->map(function ($rate) use ($outputSold, $outputReturned, $inputBought, $inputReturned) {
            $output = Money::round(Money::sub($outputSold[$rate]->tax ?? '0', $outputReturned[$rate]->tax ?? '0'));
            $input = Money::round(Money::sub($inputBought[$rate]->tax ?? '0', $inputReturned[$rate]->tax ?? '0'));

            return (object) [
                'rate' => $rate,
                'output_taxable' => Money::round(Money::sub($outputSold[$rate]->taxable ?? '0', $outputReturned[$rate]->taxable ?? '0')),
                'output_tax' => $output,
                'input_taxable' => Money::round(Money::sub($inputBought[$rate]->taxable ?? '0', $inputReturned[$rate]->taxable ?? '0')),
                'input_tax' => $input,
                'net' => Money::round(Money::sub($output, $input)),
            ];
        });

        $outputTotal = Money::round(Money::add(...$rows->pluck('output_tax')->all()));
        $inputTotal = Money::round(Money::add(...$rows->pluck('input_tax')->all()));

        return [
            'rows' => $rows,
            'output_total' => $outputTotal,
            'input_total' => $inputTotal,
            'net_payable' => Money::round(Money::sub($outputTotal, $inputTotal)),
        ];
    }

    private function completedSales(string $from, string $to, ?int $branchId): Builder
    {
        return DB::table('sales')
            ->where('status', SaleStatus::Completed->value)
            ->whereBetween('sale_date', [$from, $to])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }

    private function saleReturns(string $from, string $to, ?int $branchId): Builder
    {
        return DB::table('sale_returns')
            ->whereBetween('return_date', [$from, $to])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }

    /** @return array<string, string|int> */
    private function rounded(object $row): array
    {
        return collect((array) $row)->map(fn ($v, $k) => in_array($k, ['invoices', 'orders'], true) ? (int) $v : Money::round($v))->all();
    }
}
