<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\BranchRequest;
use App\Models\Branch;
use App\Models\Purchase;
use App\Models\Sale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class BranchController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('can:branches.manage')];
    }

    public function index(): View
    {
        $branches = Branch::query()->withCount('users')->orderBy('name')->paginate(25)->withQueryString();

        return view('settings.branches', compact('branches'));
    }

    public function store(BranchRequest $request): RedirectResponse
    {
        Branch::create($request->validated());

        return redirect()->route('settings.branches')->with('success', 'Branch created.');
    }

    public function update(BranchRequest $request, Branch $branch): RedirectResponse
    {
        $branch->update($request->validated());

        return redirect()->route('settings.branches')->with('success', 'Branch updated.');
    }

    /** Only unused branches can be deleted; otherwise deactivate. */
    public function destroy(Branch $branch): RedirectResponse
    {
        $inUse = $branch->users()->exists()
            || $branch->stockLevels()->exists()
            || Sale::where('branch_id', $branch->id)->exists()
            || Purchase::where('branch_id', $branch->id)->exists();

        if ($inUse) {
            return back()->with('error', 'This branch has users, stock or transactions and cannot be deleted. Deactivate it instead.');
        }

        $branch->delete();

        return redirect()->route('settings.branches')->with('success', 'Branch deleted.');
    }
}
