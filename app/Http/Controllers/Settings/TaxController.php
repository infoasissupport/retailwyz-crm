<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\TaxRequest;
use App\Models\Tax;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

/**
 * Rate changes apply to new documents only: sale and purchase lines store the
 * tax rate used at the time.
 */
class TaxController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('can:settings.manage')];
    }

    public function index(): View
    {
        $taxes = Tax::query()->withCount('products')->orderBy('rate')->get();

        return view('settings.taxes', compact('taxes'));
    }

    public function store(TaxRequest $request): RedirectResponse
    {
        Tax::create($request->validated());

        return redirect()->route('settings.taxes')->with('success', 'Tax created.');
    }

    public function update(TaxRequest $request, Tax $tax): RedirectResponse
    {
        $tax->update($request->validated());

        return redirect()->route('settings.taxes')->with('success', 'Tax updated.');
    }

    public function destroy(Tax $tax): RedirectResponse
    {
        if ($tax->products()->withTrashed()->exists()) {
            return back()->with('error', 'This tax is used by products and cannot be deleted. Deactivate it instead.');
        }

        $tax->delete();

        return redirect()->route('settings.taxes')->with('success', 'Tax deleted.');
    }
}
