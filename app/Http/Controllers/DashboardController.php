<?php

namespace App\Http\Controllers;

use App\Enums\DeliveryStatus;
use App\Enums\PurchaseStatus;
use App\Enums\SaleStatus;
use App\Models\Customer;
use App\Models\Delivery;
use App\Models\Lead;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\User;
use App\Services\ReportService;
use App\Support\Money;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Every signed-in user lands here after login, so instead of a blanket
 * permission check each tile and panel is computed only when the user may
 * see it. All figures respect the user's branch access.
 *
 * The selected period (default: today) is compared with the period of the
 * same length immediately before it.
 */
class DashboardController extends Controller
{
    public const PRESETS = [
        'today' => 'Today',
        'yesterday' => 'Yesterday',
        '7d' => 'Last 7 days',
        '30d' => 'Last 30 days',
        'month' => 'This month',
        'last_month' => 'Last month',
    ];

    public function __construct(private ReportService $reports) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $branchId = $this->branchFilter($request);
        [$from, $to, $preset] = $this->period($request);

        $days = (int) $from->diffInDays($to) + 1;
        $prevTo = $from->copy()->subDay();
        $prevFrom = $prevTo->copy()->subDays($days - 1);
        // Sparklines: one point per day, at least a week, at most a month, ending at `to`.
        $sparkFrom = $to->copy()->subDays(max(7, min(31, $days)) - 1);

        $ctx = compact('user', 'branchId', 'from', 'to', 'prevFrom', 'prevTo', 'sparkFrom');
        $comparison = $days === 1 ? ($preset === 'today' ? 'vs yesterday' : 'vs previous day') : "vs previous {$days} days";

        $kpis = array_values(array_filter([
            $user->can('sales.view') ? $this->salesKpi($ctx, $comparison) : null,
            $user->can('sales.view') ? $this->ordersKpi($ctx, $comparison) : null,
            $user->can('payments.view') ? $this->collectedKpi($ctx, $comparison) : null,
            $user->can('leads.view') ? $this->enquiriesKpi($ctx, $comparison) : null,
            $user->can('payments.view') ? $this->pendingPaymentsKpi($ctx) : null,
            $user->can('inventory.view') ? $this->lowStockKpi($ctx) : null,
            $user->can('customers.view') ? $this->customersKpi($ctx, $comparison) : null,
            $user->can('deliveries.view') ? $this->deliveriesKpi($ctx, $comparison) : null,
        ]));

        $view = [
            'kpis' => $user->can('dashboard.view') ? $kpis : [],
            'from' => $from,
            'to' => $to,
            'preset' => $preset,
            'presets' => self::PRESETS,
        ];

        if ($user->can('dashboard.view')) {
            if ($user->can('leads.view')) {
                $view['recentLeads'] = $this->scoped(Lead::query(), $ctx)->latest('id')->limit(5)->get();
            }
            if ($user->can('deliveries.view')) {
                $view['todaysDeliveries'] = $this->todaysDeliveries($ctx);
            }
            if ($user->can('sales.view')) {
                $view['activeOrders'] = $this->scoped(Sale::query(), $ctx)
                    ->with(['customer:id,name', 'items:id,sale_id,description'])
                    ->where('status', SaleStatus::Completed->value)
                    ->whereRaw('grand_total - returned_amount - paid_amount > 0')
                    ->latest('sale_date')->latest('id')
                    ->limit(5)
                    ->get();
            }
        }

        return view('dashboard.index', $view);
    }

    // ------------------------------------------------------------------ KPIs

    private function salesKpi(array $c, string $comparison): array
    {
        $sum = fn ($a, $b) => (string) $this->completedSales($c)->whereBetween('sale_date', $this->range($a, $b))->sum('grand_total');
        $current = $sum($c['from'], $c['to']);

        return $this->kpi('Total sales', 'bi-graph-up-arrow', 'green', money(Money::round($current)), $current, $sum($c['prevFrom'], $c['prevTo']), $comparison,
            $this->daily($this->completedSales($c), 'sale_date', 'SUM(grand_total)', $c), route('sales.index', ['from' => $c['from']->toDateString(), 'to' => $c['to']->toDateString()]));
    }

    private function ordersKpi(array $c, string $comparison): array
    {
        $count = fn ($a, $b) => $this->completedSales($c)->whereBetween('sale_date', $this->range($a, $b))->count();
        $current = $count($c['from'], $c['to']);

        return $this->kpi('Orders', 'bi-receipt', 'blue', (string) $current, $current, $count($c['prevFrom'], $c['prevTo']), $comparison,
            $this->daily($this->completedSales($c), 'sale_date', 'COUNT(*)', $c), route('sales.index', ['from' => $c['from']->toDateString(), 'to' => $c['to']->toDateString()]));
    }

    private function collectedKpi(array $c, string $comparison): array
    {
        $net = "SUM(CASE WHEN direction = 'in' THEN amount ELSE -amount END)";
        $sum = fn ($a, $b) => (string) ($this->salePayments($c)->whereBetween('paid_on', $this->range($a, $b))->selectRaw("COALESCE({$net}, 0) as v")->value('v') ?? 0);
        $current = $sum($c['from'], $c['to']);

        return $this->kpi('Collected', 'bi-wallet2', 'orange', money(Money::round($current)), $current, $sum($c['prevFrom'], $c['prevTo']), $comparison,
            $this->daily($this->salePayments($c), 'paid_on', $net, $c), route('payments.index'));
    }

    private function enquiriesKpi(array $c, string $comparison): array
    {
        $count = fn ($a, $b) => $this->scoped(Lead::query(), $c)->whereBetween('created_at', $this->range($a, $b))->count();
        $current = $count($c['from'], $c['to']);

        return $this->kpi('Enquiries', 'bi-person-plus', 'teal', (string) $current, $current, $count($c['prevFrom'], $c['prevTo']), $comparison,
            $this->daily($this->scoped(Lead::query(), $c), 'created_at', 'COUNT(*)', $c), route('leads.index'));
    }

    private function pendingPaymentsKpi(array $c): array
    {
        $row = $this->scoped(Sale::query(), $c)
            ->where('status', SaleStatus::Completed->value)
            ->whereRaw('grand_total - returned_amount - paid_amount > 0')
            ->selectRaw('COUNT(*) as invoices, COALESCE(SUM(grand_total - returned_amount - paid_amount), 0) as due')
            ->first();

        return $this->kpi('Pending payments', 'bi-currency-rupee', 'rose', money(Money::round($row->due)), null, null,
            (int) $row->invoices.' unpaid '.str('invoice')->plural((int) $row->invoices), null, route('payments.receivables'));
    }

    private function lowStockKpi(array $c): array
    {
        $low = $this->reports->lowStock($c['branchId'] ?? ($c['user']->canAccessAllBranches() ? null : 0))->count();
        $out = $this->reports->lowStock($c['branchId'] ?? ($c['user']->canAccessAllBranches() ? null : 0))->where('quantity', '<=', 0)->count();

        return $this->kpi('Low stock items', 'bi-exclamation-triangle', 'amber', (string) $low, null, null,
            $out ? "{$out} out of stock" : 'none out of stock', null, route('inventory.index', ['low_stock' => 1]));
    }

    private function customersKpi(array $c, string $comparison): array
    {
        $new = fn ($a, $b) => Customer::query()->whereBetween('created_at', $this->range($a, $b))->count();
        $added = $new($c['from'], $c['to']);

        return $this->kpi('Total customers', 'bi-people', 'violet', (string) Customer::query()->count(), $added, $new($c['prevFrom'], $c['prevTo']),
            "+{$added} new · {$comparison}", $this->daily(Customer::query(), 'created_at', 'COUNT(*)', $c), route('customers.index'));
    }

    private function deliveriesKpi(array $c, string $comparison): array
    {
        $count = fn ($a, $b) => $this->scoped(Delivery::query(), $c)->where('status', '!=', DeliveryStatus::Cancelled->value)->whereBetween('scheduled_date', $this->range($a, $b))->count();
        $current = $count($c['from'], $c['to']);
        $delivered = $this->scoped(Delivery::query(), $c)->where('status', DeliveryStatus::Delivered->value)->whereBetween('scheduled_date', $this->range($c['from'], $c['to']))->count();

        return $this->kpi('Deliveries', 'bi-truck', 'sky', (string) $current, $current, $count($c['prevFrom'], $c['prevTo']), "{$delivered} delivered · {$comparison}",
            $this->daily($this->scoped(Delivery::query(), $c)->where('status', '!=', DeliveryStatus::Cancelled->value), 'scheduled_date', 'COUNT(*)', $c), route('deliveries.index'));
    }

    /**
     * @param  list<float>|null  $spark
     */
    private function kpi(string $label, string $icon, string $tone, string $display, string|int|null $current, string|int|null $previous, string $caption, ?array $spark, string $url): array
    {
        // No percentage against a zero base: "+100%" from nothing would mislead.
        $delta = null;
        if ($current !== null && $previous !== null && Money::cmp($previous, '0') !== 0) {
            $delta = (float) Money::round(Money::mul(Money::div(Money::sub($current, $previous), $previous), '100'), 1);
        }

        return compact('label', 'icon', 'tone', 'display', 'delta', 'caption', 'spark', 'url');
    }

    // ------------------------------------------------------------------ Panels

    private function todaysDeliveries(array $c)
    {
        return $this->scoped(Delivery::query(), $c)
            ->with('sale:id,invoice_no')
            ->where(fn ($q) => $q
                ->whereDate('scheduled_date', today())
                ->orWhere('status', DeliveryStatus::OutForDelivery->value)
                ->orWhereDate('delivered_at', today()))
            ->where('status', '!=', DeliveryStatus::Cancelled->value)
            ->orderByRaw("CASE status WHEN 'out_for_delivery' THEN 0 WHEN 'pending' THEN 1 WHEN 'failed' THEN 2 ELSE 3 END")
            ->orderBy('time_slot')
            ->limit(5)
            ->get();
    }

    // ------------------------------------------------------------------ Helpers

    /** @return array{0: Carbon, 1: Carbon, 2: ?string} */
    private function period(Request $request): array
    {
        $preset = $request->query('range');

        if (! $preset && $request->filled(['from', 'to'])) {
            $from = $request->date('from');
            $to = $request->date('to');
            if ($from && $to && $from->lte($to) && $from->diffInDays($to) <= 366) {
                return [$from->startOfDay(), $to->startOfDay(), null];
            }
        }

        $preset = array_key_exists((string) $preset, self::PRESETS) ? $preset : 'today';

        return match ($preset) {
            'yesterday' => [today()->subDay(), today()->subDay(), $preset],
            '7d' => [today()->subDays(6), today(), $preset],
            '30d' => [today()->subDays(29), today(), $preset],
            'month' => [today()->startOfMonth(), today(), $preset],
            'last_month' => [today()->subMonthNoOverflow()->startOfMonth(), today()->subMonthNoOverflow()->endOfMonth()->startOfDay(), $preset],
            default => [today(), today(), 'today'],
        };
    }

    /** Inclusive date range; the upper bound covers the whole last day on every driver. */
    private function range(Carbon $from, Carbon $to): array
    {
        return [$from->toDateString(), $to->toDateString().' 23:59:59'];
    }

    private function scoped(Builder $query, array $c): Builder
    {
        return $query->visibleTo($c['user'])->when($c['branchId'], fn ($q, $id) => $q->where('branch_id', $id));
    }

    private function completedSales(array $c): Builder
    {
        return $this->scoped(Sale::query(), $c)->where('status', SaleStatus::Completed->value);
    }

    private function salePayments(array $c): Builder
    {
        return $this->scoped(Payment::query(), $c)->where('payable_type', (new Sale)->getMorphClass());
    }

    /**
     * Daily aggregate between sparkFrom and to, zero-filled.
     *
     * @return list<float>
     */
    private function daily(Builder $query, string $column, string $aggregate, array $c): array
    {
        $rows = $query
            ->whereBetween($column, $this->range($c['sparkFrom'], $c['to']))
            ->selectRaw("DATE({$column}) as d, {$aggregate} as v")
            ->groupByRaw("DATE({$column})")
            ->pluck('v', 'd')
            ->mapWithKeys(fn ($v, $d) => [substr((string) $d, 0, 10) => (float) $v]);

        return collect(CarbonPeriod::create($c['sparkFrom'], $c['to']))
            ->map(fn ($day) => $rows[$day->toDateString()] ?? 0.0)
            ->values()
            ->all();
    }
}
