<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class ReportController extends Controller implements HasMiddleware
{
    public function __construct(private ReportService $reports) {}

    public static function middleware(): array
    {
        return [
            new Middleware('can:reports.view'),
            new Middleware('can:reports.financial', only: ['profitLoss', 'tax']),
        ];
    }

    public function index(): View
    {
        return view('reports.index');
    }

    public function sales(Request $request): View
    {
        [$from, $to] = $this->period($request);

        return view('reports.sales', $this->reports->sales($from, $to.' 23:59:59', $this->branchFilter($request)) + compact('from', 'to'));
    }

    public function purchases(Request $request): View
    {
        [$from, $to] = $this->period($request);

        return view('reports.purchases', $this->reports->purchases($from, $to.' 23:59:59', $this->branchFilter($request)) + compact('from', 'to'));
    }

    public function stock(Request $request): View
    {
        $branchId = $this->branchFilter($request);

        return view('reports.stock', [
            'rows' => $this->reports->stockValuation($branchId)->paginate(50)->withQueryString(),
            'totalValue' => $request->user()->can('products.view-cost') ? $this->reports->stockValueTotal($branchId) : null,
            'lowStock' => $this->reports->lowStock($branchId),
        ]);
    }

    public function profitLoss(Request $request): View
    {
        [$from, $to] = $this->period($request);

        return view('reports.profit-loss', [
            'report' => $this->reports->profitAndLoss($from, $to.' 23:59:59', $this->branchFilter($request)),
        ] + compact('from', 'to'));
    }

    public function tax(Request $request): View
    {
        [$from, $to] = $this->period($request);

        return view('reports.tax', [
            'report' => $this->reports->tax($from, $to.' 23:59:59', $this->branchFilter($request)),
        ] + compact('from', 'to'));
    }

    /**
     * Inclusive date range, default the current month. The end bound gets a
     * time suffix in the queries so dates stored with a time part still match.
     *
     * @return array{0: string, 1: string}
     */
    private function period(Request $request): array
    {
        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        return [
            ($request->date('from') ?? now()->startOfMonth())->toDateString(),
            ($request->date('to') ?? now()->endOfMonth())->toDateString(),
        ];
    }
}
