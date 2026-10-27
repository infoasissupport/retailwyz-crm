<?php

namespace App\Http\Controllers;

use App\Enums\AdjustmentStatus;
use App\Http\Requests\StockAdjustmentRequest;
use App\Models\Branch;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Services\StockAdjustmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class StockAdjustmentController extends Controller implements HasMiddleware
{
    public function __construct(private StockAdjustmentService $adjustments) {}

    public static function middleware(): array
    {
        return [
            new Middleware('can:inventory.view', only: ['index']),
            new Middleware('can:inventory.adjust', only: ['stockIn', 'stockOut', 'store']),
        ];
    }

    public function index(Request $request): View
    {
        $adjustments = StockAdjustment::query()
            ->with(['branch:id,name', 'creator:id,name', 'approver:id,name'])
            ->withCount('items')
            ->visibleTo($request->user())
            ->when($this->branchFilter($request), fn ($q, $id) => $q->where('branch_id', $id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('inventory.adjustments', [
            'adjustments' => $adjustments,
            'statuses' => collect(AdjustmentStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()]),
        ]);
    }

    public function stockIn(Request $request): View
    {
        return view('inventory.stock-in', $this->formOptions($request));
    }

    public function stockOut(Request $request): View
    {
        return view('inventory.stock-out', $this->formOptions($request));
    }

    public function store(StockAdjustmentRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = ['branch_id' => $this->resolveBranchId($request)] + $request->validated();

        // Direct permission check: the approve policy forbids self-approval of an existing adjustment.
        $approveNow = $request->boolean('approve_now') && $user->can('inventory.approve');

        $adjustment = $this->adjustments->create($data, $user, approveNow: $approveNow);

        return redirect()->route('inventory.adjustments.index')
            ->with('success', "Adjustment {$adjustment->reference_no} ".($approveNow ? 'applied.' : 'submitted for approval.'));
    }

    public function approve(Request $request, StockAdjustment $adjustment): RedirectResponse
    {
        Gate::authorize('approve', $adjustment);

        $this->adjustments->approve($adjustment, $request->user());

        return back()->with('success', "Adjustment {$adjustment->reference_no} approved.");
    }

    public function reject(Request $request, StockAdjustment $adjustment): RedirectResponse
    {
        Gate::authorize('approve', $adjustment);

        $this->adjustments->reject($adjustment, $request->user());

        return back()->with('success', "Adjustment {$adjustment->reference_no} rejected.");
    }

    private function formOptions(Request $request): array
    {
        return [
            'products' => Product::active()->orderBy('name')->limit(500)->get(['id', 'name', 'sku'])
                ->mapWithKeys(fn ($p) => [$p->id => "{$p->name} ({$p->sku})"]),
            'branches' => $request->user()->canAccessAllBranches() ? Branch::active()->orderBy('name')->pluck('name', 'id') : collect(),
        ];
    }
}
