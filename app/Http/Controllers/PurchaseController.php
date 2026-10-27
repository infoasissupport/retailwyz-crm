<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Enums\PurchaseStatus;
use App\Http\Requests\PurchaseRequest;
use App\Http\Requests\ReceivePurchaseRequest;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Services\PurchaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PurchaseController extends Controller implements HasMiddleware
{
    public function __construct(private PurchaseService $purchases) {}

    public static function middleware(): array
    {
        return [
            new Middleware('can:purchases.view', only: ['index', 'show']),
            new Middleware('can:purchases.create', only: ['create', 'store']),
            new Middleware('can:purchases.update', only: ['edit', 'update']),
        ];
    }

    public function index(Request $request): View
    {
        $purchases = Purchase::query()
            ->with(['supplier:id,name', 'branch:id,name'])
            ->visibleTo($request->user())
            ->when($this->branchFilter($request), fn ($q, $id) => $q->where('branch_id', $id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->integer('supplier_id'), fn ($q, $id) => $q->where('supplier_id', $id))
            ->when($request->date('from'), fn ($q, $from) => $q->whereDate('purchase_date', '>=', $from))
            ->when($request->date('to'), fn ($q, $to) => $q->whereDate('purchase_date', '<=', $to))
            ->when($request->string('q')->trim()->toString(), fn ($q, $term) => $q->where(fn ($q) => $q
                ->where('reference_no', 'like', "%{$term}%")
                ->orWhere('supplier_invoice_no', 'like', "%{$term}%")))
            ->latest('purchase_date')
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('purchases.index', [
            'purchases' => $purchases,
            'suppliers' => Supplier::orderBy('name')->pluck('name', 'id'),
            'statuses' => collect(PurchaseStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()]),
        ]);
    }

    public function create(Request $request): View
    {
        $purchase = new Purchase(['purchase_date' => now()]);
        $purchase->setRelation('items', collect());

        return view('purchases.create', ['purchase' => $purchase] + $this->formOptions($request));
    }

    public function store(PurchaseRequest $request): RedirectResponse
    {
        $purchase = $this->purchases->create(
            ['branch_id' => $this->resolveBranchId($request)] + $request->purchaseData(),
            $request->user(),
        );

        return redirect()->route('purchases.show', $purchase)->with('success', "Purchase {$purchase->reference_no} created.");
    }

    public function show(Purchase $purchase): View
    {
        Gate::authorize('view', $purchase);

        $purchase->load([
            'supplier', 'branch', 'creator:id,name',
            'items.product:id,name,sku', 'items.variant:id,name',
            'returns.creator:id,name',
            'payments.creator:id,name',
        ]);

        return view('purchases.show', [
            'purchase' => $purchase,
            'paymentMethods' => collect(PaymentMethod::cases())->mapWithKeys(fn ($m) => [$m->value => $m->label()]),
        ]);
    }

    public function edit(Request $request, Purchase $purchase): View
    {
        Gate::authorize('update', $purchase);

        $purchase->load('items');

        return view('purchases.edit', ['purchase' => $purchase] + $this->formOptions($request));
    }

    public function update(PurchaseRequest $request, Purchase $purchase): RedirectResponse
    {
        Gate::authorize('update', $purchase);

        $this->purchases->update($purchase, $request->purchaseData());

        return redirect()->route('purchases.show', $purchase)->with('success', 'Purchase updated.');
    }

    public function receive(ReceivePurchaseRequest $request, Purchase $purchase): RedirectResponse
    {
        Gate::authorize('receive', $purchase);

        $this->purchases->receive($purchase, $request->quantities(), $request->user());

        return redirect()->route('purchases.show', $purchase)->with('success', 'Goods received into stock.');
    }

    public function cancel(Purchase $purchase): RedirectResponse
    {
        Gate::authorize('cancel', $purchase);

        $this->purchases->cancel($purchase);

        return redirect()->route('purchases.show', $purchase)->with('success', 'Purchase cancelled.');
    }

    private function formOptions(Request $request): array
    {
        return [
            'suppliers' => Supplier::active()->orderBy('name')->pluck('name', 'id'),
            // TODO (page design): replace with a search wired to route('products.lookup').
            'products' => Product::active()->orderBy('name')->limit(500)->get(['id', 'name', 'sku'])
                ->mapWithKeys(fn ($p) => [$p->id => "{$p->name} ({$p->sku})"]),
            'branches' => $request->user()->canAccessAllBranches() ? Branch::active()->orderBy('name')->pluck('name', 'id') : collect(),
            'statuses' => [PurchaseStatus::Ordered->value => PurchaseStatus::Ordered->label(), PurchaseStatus::Draft->value => PurchaseStatus::Draft->label()],
        ];
    }
}
