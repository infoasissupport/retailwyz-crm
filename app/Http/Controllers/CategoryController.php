<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\GeneratesSlugs;
use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class CategoryController extends Controller implements HasMiddleware
{
    use GeneratesSlugs;

    public static function middleware(): array
    {
        return [new Middleware('can:catalog.manage')];
    }

    public function index(Request $request): View
    {
        $categories = Category::query()
            ->with('parent:id,name')
            ->withCount('products')
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.$request->string('q')->trim().'%'))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('categories.create', ['category' => new Category(['is_active' => true]), 'parents' => $this->parents()]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $category = new Category($request->validated());
        $category->slug = $this->uniqueSlug($category, $category->name);
        $category->save();

        return redirect()->route('categories.index')->with('success', 'Category created.');
    }

    public function edit(Category $category): View
    {
        return view('categories.edit', ['category' => $category, 'parents' => $this->parents($category)]);
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $category->fill($request->validated());
        if ($category->isDirty('name')) {
            $category->slug = $this->uniqueSlug($category, $category->name);
        }
        $category->save();

        return redirect()->route('categories.index')->with('success', 'Category updated.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->products()->withTrashed()->exists()) {
            return back()->with('error', 'This category has products and cannot be deleted. Deactivate it instead.');
        }

        $category->delete();

        return redirect()->route('categories.index')->with('success', 'Category deleted.');
    }

    private function parents(?Category $except = null)
    {
        return Category::query()
            ->when($except, fn ($q) => $q->whereKeyNot($except->id))
            ->orderBy('name')
            ->pluck('name', 'id');
    }
}
