<?php

namespace App\Http\Controllers;

use App\Models\ExpenseCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ExpenseCategoryController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:expenses.view', only: ['index']),
            new Middleware('can:expenses.manage', except: ['index']),
        ];
    }

    public function index(): View
    {
        return view('expenses.categories', [
            'categories' => ExpenseCategory::withCount('expenses')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        ExpenseCategory::create($this->validated($request));

        return back()->with('success', 'Expense category created.');
    }

    public function update(Request $request, ExpenseCategory $expenseCategory): RedirectResponse
    {
        $expenseCategory->update($this->validated($request, $expenseCategory));

        return back()->with('success', 'Expense category updated.');
    }

    public function destroy(ExpenseCategory $expenseCategory): RedirectResponse
    {
        if ($expenseCategory->expenses()->withTrashed()->exists()) {
            return back()->with('error', 'This category has expenses. Deactivate it instead.');
        }

        $expenseCategory->delete();

        return back()->with('success', 'Expense category deleted.');
    }

    private function validated(Request $request, ?ExpenseCategory $category = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('expense_categories', 'name')->ignore($category)],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ]);
    }
}
