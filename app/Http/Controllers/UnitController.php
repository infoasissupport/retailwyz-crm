<?php

namespace App\Http\Controllers;

use App\Http\Requests\UnitRequest;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class UnitController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('can:catalog.manage')];
    }

    public function index(): View
    {
        $units = Unit::query()->withCount('products')->orderBy('name')->paginate(25)->withQueryString();

        return view('units.index', compact('units'));
    }

    public function create(): View
    {
        return view('units.create', ['unit' => new Unit(['is_active' => true])]);
    }

    public function store(UnitRequest $request): RedirectResponse
    {
        Unit::create($request->validated());

        return redirect()->route('units.index')->with('success', 'Unit created.');
    }

    public function edit(Unit $unit): View
    {
        return view('units.edit', compact('unit'));
    }

    public function update(UnitRequest $request, Unit $unit): RedirectResponse
    {
        $unit->update($request->validated());

        return redirect()->route('units.index')->with('success', 'Unit updated.');
    }

    public function destroy(Unit $unit): RedirectResponse
    {
        if ($unit->products()->withTrashed()->exists()) {
            return back()->with('error', 'This unit is used by products and cannot be deleted. Deactivate it instead.');
        }

        $unit->delete();

        return redirect()->route('units.index')->with('success', 'Unit deleted.');
    }
}
