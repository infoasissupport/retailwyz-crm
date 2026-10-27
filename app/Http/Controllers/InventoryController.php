<?php

namespace App\Http\Controllers;

use App\Enums\StockMovementType;
use App\Models\Branch;
use App\Models\Product;
use App\Models\StockLevel;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class InventoryController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('can:inventory.view')];
    }

    public function index(Request $request): View
    {
        $term = $request->string('q')->trim()->toString();

        $levels = StockLevel::query()
            ->with(['product:id,name,sku,min_stock,unit_id', 'product.unit:id,short_name', 'variant:id,name', 'branch:id,name'])
            ->visibleTo($request->user())
            ->when($this->branchFilter($request), fn ($q, $id) => $q->where('stock_levels.branch_id', $id))
            ->when($term, fn ($q) => $q->whereHas('product', fn ($p) => $p->search($term)))
            ->when($request->boolean('low_stock'), fn ($q) => $q->whereHas(
                'product', fn ($p) => $p->whereColumn('stock_levels.quantity', '<=', 'products.min_stock')
            ))
            ->orderByDesc('updated_at')
            ->paginate(25)
            ->withQueryString();

        return view('inventory.index', [
            'levels' => $levels,
            'branches' => $this->branchOptions($request),
        ]);
    }

    public function movements(Request $request): View
    {
        $movements = StockMovement::query()
            ->with(['product:id,name', 'branch:id,name', 'creator:id,name'])
            ->visibleTo($request->user())
            ->when($this->branchFilter($request), fn ($q, $id) => $q->where('branch_id', $id))
            ->when($request->integer('product_id'), fn ($q, $id) => $q->where('product_id', $id))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->when($request->date('from'), fn ($q, $from) => $q->where('created_at', '>=', $from->startOfDay()))
            ->when($request->date('to'), fn ($q, $to) => $q->where('created_at', '<=', $to->endOfDay()))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('inventory.movements', [
            'movements' => $movements,
            'products' => Product::orderBy('name')->limit(500)->pluck('name', 'id'),
            'types' => collect(StockMovementType::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()]),
            'branches' => $this->branchOptions($request),
        ]);
    }

    private function branchOptions(Request $request)
    {
        return $request->user()->canAccessAllBranches()
            ? Branch::orderBy('name')->pluck('name', 'id')
            : collect();
    }
}
