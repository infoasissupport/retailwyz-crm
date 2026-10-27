<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\GeneratesSlugs;
use App\Http\Requests\BrandRequest;
use App\Models\Brand;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class BrandController extends Controller implements HasMiddleware
{
    use GeneratesSlugs;

    public static function middleware(): array
    {
        return [new Middleware('can:catalog.manage')];
    }

    public function index(Request $request): View
    {
        $brands = Brand::query()
            ->withCount('products')
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.$request->string('q')->trim().'%'))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('brands.index', compact('brands'));
    }

    public function create(): View
    {
        return view('brands.create', ['brand' => new Brand(['is_active' => true])]);
    }

    public function store(BrandRequest $request): RedirectResponse
    {
        $brand = new Brand($request->validated());
        $brand->slug = $this->uniqueSlug($brand, $brand->name);
        $brand->save();

        return redirect()->route('brands.index')->with('success', 'Brand created.');
    }

    public function edit(Brand $brand): View
    {
        return view('brands.edit', compact('brand'));
    }

    public function update(BrandRequest $request, Brand $brand): RedirectResponse
    {
        $brand->fill($request->validated());
        if ($brand->isDirty('name')) {
            $brand->slug = $this->uniqueSlug($brand, $brand->name);
        }
        $brand->save();

        return redirect()->route('brands.index')->with('success', 'Brand updated.');
    }

    public function destroy(Brand $brand): RedirectResponse
    {
        if ($brand->products()->withTrashed()->exists()) {
            return back()->with('error', 'This brand has products and cannot be deleted. Deactivate it instead.');
        }

        $brand->delete();

        return redirect()->route('brands.index')->with('success', 'Brand deleted.');
    }
}
