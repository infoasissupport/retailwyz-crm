<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SaleStatus;
use App\Http\Requests\SaleRequest;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Services\SaleService;
use App\Services\SettingsService;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SaleController extends Controller implements HasMiddleware
{
    public function __construct(private SaleService $sales) {}

    public static function middleware(): array
    {
        return [
            new Middleware('can:sales.view', only: ['index', 'show', 'invoice']),
            new Middleware('can:sales.create', only: ['create', 'store']),
        ];
    }

    public function index(Request $request): View
    {
        $sales = Sale::query()
            ->with(['customer:id,name', 'branch:id,name'])
            ->visibleTo($request->user())
            ->when($this->branchFilter($request), fn ($q, $id) => $q->where('branch_id', $id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('payment_status'), fn ($q) => $q->where('payment_status', $request->string('payment_status')))
            ->when($request->integer('customer_id'), fn ($q, $id) => $q->where('customer_id', $id))
            ->when($request->date('from'), fn ($q, $from) => $q->whereDate('sale_date', '>=', $from))
            ->when($request->date('to'), fn ($q, $to) => $q->whereDate('sale_date', '<=', $to))
            ->when($request->string('q')->trim()->toString(), fn ($q, $term) => $q->where('invoice_no', 'like', "%{$term}%"))
            ->latest('sale_date')
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('sales.index', [
            'sales' => $sales,
            'customers' => Customer::orderBy('name')->limit(500)->pluck('name', 'id'),
            'statuses' => collect(SaleStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()]),
            'paymentStatuses' => collect(PaymentStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()]),
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Sale::class);

        return view('sales.create', [
            'customers' => Customer::active()->orderBy('name')->limit(500)->pluck('name', 'id'),
            // TODO (page design): replace with a search wired to route('products.lookup').
            'products' => Product::active()->orderBy('name')->limit(500)->get(['id', 'name', 'sku'])
                ->mapWithKeys(fn ($p) => [$p->id => "{$p->name} ({$p->sku})"]),
            'branches' => $request->user()->canAccessAllBranches() ? Branch::active()->orderBy('name')->pluck('name', 'id') : collect(),
            'paymentMethods' => $this->paymentMethods(),
            // One key per rendered form: a resubmission returns the existing sale instead of duplicating it.
            'idempotencyKey' => (string) Str::uuid(),
        ]);
    }

    public function store(SaleRequest $request): RedirectResponse
    {
        Gate::authorize('create', Sale::class);

        $sale = $this->sales->create(
            ['branch_id' => $this->resolveBranchId($request)] + $request->saleData(),
            $request->user(),
        );

        return redirect()->route('sales.show', $sale)->with('success', "Sale {$sale->invoice_no} saved.");
    }

    public function show(Sale $sale): View
    {
        Gate::authorize('view', $sale);

        $sale->load([
            'customer', 'branch', 'creator:id,name',
            'items.product:id,name,sku',
            'returns.creator:id,name',
            'payments.creator:id,name',
        ]);

        return view('sales.show', [
            'sale' => $sale,
            'paymentMethods' => $this->paymentMethods(),
        ]);
    }

    public function invoice(Sale $sale, SettingsService $settings): View
    {
        Gate::authorize('view', $sale);

        $sale->load(['customer', 'branch', 'items', 'payments']);

        return view('sales.invoice', [
            'sale' => $sale,
            'settings' => $settings,
            'taxBreakdown' => $sale->items->groupBy(fn ($item) => (string) $item->tax_rate)
                ->map(function ($items, $rate) {
                    $tax = Money::add(...$items->pluck('tax_amount')->all());

                    return [
                        'rate' => $rate,
                        'taxable' => Money::round(Money::sub(Money::add(...$items->pluck('line_total')->all()), $tax)),
                        'tax' => Money::round($tax),
                    ];
                })->values(),
        ]);
    }

    public function cancel(Request $request, Sale $sale): RedirectResponse
    {
        Gate::authorize('cancel', $sale);

        $this->sales->cancel($sale, $request->user());

        return redirect()->route('sales.show', $sale)->with('success', "Sale {$sale->invoice_no} cancelled and stock restored.");
    }

    private function paymentMethods()
    {
        return collect(PaymentMethod::cases())->mapWithKeys(fn ($m) => [$m->value => $m->label()]);
    }
}
