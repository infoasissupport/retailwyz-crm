<?php

namespace App\Http\Controllers;

use App\Enums\ProductStatus;
use App\Http\Requests\ProductRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Tax;
use App\Models\Unit;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class ProductController extends Controller implements HasMiddleware
{
    public function __construct(private ProductService $products) {}

    public static function middleware(): array
    {
        return [
            new Middleware('can:products.view', only: ['index', 'show']),
            new Middleware('can:products.create', only: ['create', 'store']),
            new Middleware('can:products.update', only: ['edit', 'update']),
            new Middleware('can:products.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $products = Product::query()
            ->with(['category:id,name', 'brand:id,name', 'unit:id,short_name'])
            ->withStock($this->branchFilter($request))
            ->search($request->string('q')->trim()->toString())
            ->when($request->integer('category_id'), fn ($q, $id) => $q->where('category_id', $id))
            ->when($request->integer('brand_id'), fn ($q, $id) => $q->where('brand_id', $id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('products.index', [
            'products' => $products,
            'categories' => Category::orderBy('name')->pluck('name', 'id'),
            'brands' => Brand::orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function create(): View
    {
        return view('products.create', ['product' => new Product(['status' => ProductStatus::Active, 'track_stock' => true])] + $this->formOptions());
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $product = $this->products->create($request->productData(), $request->user());

        return redirect()->route('products.show', $product)->with('success', 'Product created.');
    }

    public function show(Request $request, Product $product): View
    {
        $product->load(['category', 'brand', 'unit', 'tax', 'defaultSupplier', 'variants']);

        $stockLevels = $product->stockLevels()
            ->with(['branch:id,name', 'variant:id,name'])
            ->visibleTo($request->user())
            ->get();

        $movements = $product->stockMovements()
            ->with(['branch:id,name', 'creator:id,name'])
            ->visibleTo($request->user())
            ->latest('id')
            ->limit(20)
            ->get();

        return view('products.show', compact('product', 'stockLevels', 'movements'));
    }

    public function edit(Product $product): View
    {
        $product->load('variants');

        return view('products.edit', compact('product') + $this->formOptions());
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $this->products->update($product, $request->productData());

        return redirect()->route('products.show', $product)->with('success', 'Product updated.');
    }

    /** Soft delete: history (sales, movements) keeps pointing at the product. */
    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return redirect()->route('products.index')->with('success', 'Product deleted.');
    }

    /** JSON search used by sale, purchase and stock adjustment forms. */
    public function lookup(Request $request): JsonResponse
    {
        abort_unless($request->user()->canAny(['products.view', 'sales.create', 'purchases.create', 'inventory.adjust']), 403);

        $branchId = $this->branchFilter($request);
        $showCost = $request->user()->can('products.view-cost');

        $products = Product::query()
            ->active()
            ->with(['tax:id,rate', 'unit:id,short_name,allow_decimal', 'variants' => fn ($q) => $q->where('is_active', true)])
            ->withStock($branchId)
            ->search($request->string('q')->trim()->toString())
            ->orderBy('name')
            ->limit(20)
            ->get();

        return response()->json($products->map(fn (Product $p) => [
            'id' => $p->id,
            'name' => $p->name,
            'sku' => $p->sku,
            'unit' => $p->unit->short_name,
            'allow_decimal' => $p->unit->allow_decimal,
            'selling_price' => $p->selling_price,
            'cost_price' => $showCost ? $p->cost_price : null,
            'tax_rate' => $p->tax?->rate ?? '0',
            'stock' => $p->stock_on_hand ?? '0',
            'track_stock' => $p->track_stock,
            'variants' => $p->has_variants ? $p->variants->map(fn ($v) => [
                'id' => $v->id,
                'name' => $v->name,
                'sku' => $v->sku,
                'selling_price' => $v->selling_price ?? $p->selling_price,
            ])->values() : [],
        ]));
    }

    private function formOptions(): array
    {
        return [
            'categories' => Category::active()->orderBy('name')->pluck('name', 'id'),
            'brands' => Brand::active()->orderBy('name')->pluck('name', 'id'),
            'units' => Unit::active()->orderBy('name')->get()->mapWithKeys(fn ($u) => [$u->id => "{$u->name} ({$u->short_name})"]),
            'taxes' => Tax::active()->orderBy('rate')->get()->mapWithKeys(fn ($t) => [$t->id => "{$t->name}"]),
            'suppliers' => Supplier::active()->orderBy('name')->pluck('name', 'id'),
            'statuses' => collect(ProductStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()]),
        ];
    }
}
