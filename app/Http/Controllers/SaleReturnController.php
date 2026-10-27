<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaleReturnRequest;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Services\SaleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SaleReturnController extends Controller implements HasMiddleware
{
    public function __construct(private SaleService $sales) {}

    public static function middleware(): array
    {
        return [new Middleware('can:sales.view', only: ['index'])];
    }

    public function index(Request $request): View
    {
        $returns = SaleReturn::query()
            ->with(['sale:id,invoice_no', 'customer:id,name', 'branch:id,name', 'creator:id,name'])
            ->visibleTo($request->user())
            ->when($this->branchFilter($request), fn ($q, $id) => $q->where('branch_id', $id))
            ->when($request->date('from'), fn ($q, $from) => $q->whereDate('return_date', '>=', $from))
            ->when($request->date('to'), fn ($q, $to) => $q->whereDate('return_date', '<=', $to))
            ->latest('return_date')
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('sales.returns', compact('returns'));
    }

    public function store(SaleReturnRequest $request, Sale $sale): RedirectResponse
    {
        Gate::authorize('return', $sale);

        $return = $this->sales->createReturn($sale, $request->returnData(), $request->user());

        return redirect()->route('sales.show', $sale)->with('success', "Return {$return->reference_no} recorded.");
    }
}
