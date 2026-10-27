<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Http\Requests\ExpenseRequest;
use App\Models\Branch;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Services\ExpenseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ExpenseController extends Controller implements HasMiddleware
{
    public function __construct(private ExpenseService $expenses) {}

    public static function middleware(): array
    {
        return [
            new Middleware('can:expenses.view', only: ['index']),
            new Middleware('can:expenses.manage', except: ['index']),
        ];
    }

    public function index(Request $request): View
    {
        $query = Expense::query()
            ->visibleTo($request->user())
            ->when($this->branchFilter($request), fn ($q, $id) => $q->where('branch_id', $id))
            ->when($request->integer('expense_category_id'), fn ($q, $id) => $q->where('expense_category_id', $id))
            ->when($request->date('from'), fn ($q, $d) => $q->whereDate('expense_date', '>=', $d))
            ->when($request->date('to'), fn ($q, $d) => $q->whereDate('expense_date', '<=', $d));

        return view('expenses.index', [
            'total' => (clone $query)->sum('amount'),
            'expenses' => $query->with(['category:id,name', 'branch:id,name'])
                ->latest('expense_date')->latest('id')
                ->paginate(25)->withQueryString(),
            'categories' => ExpenseCategory::orderBy('name')->pluck('name', 'id'),
            'branches' => $request->user()->canAccessAllBranches() ? Branch::orderBy('name')->pluck('name', 'id') : collect(),
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Expense::class);

        return view('expenses.create', ['expense' => new Expense(['expense_date' => now()])] + $this->formOptions($request));
    }

    public function store(ExpenseRequest $request): RedirectResponse
    {
        Gate::authorize('create', Expense::class);

        $data = $request->safe()->except('attachment');
        $data['branch_id'] = $this->resolveBranchId($request);

        $expense = $this->expenses->create($data, $request->user(), $request->file('attachment'));

        return redirect()->route('expenses.index')->with('success', "Expense {$expense->reference_no} recorded.");
    }

    public function edit(Request $request, Expense $expense): View
    {
        Gate::authorize('update', $expense);

        return view('expenses.edit', compact('expense') + $this->formOptions($request));
    }

    public function update(ExpenseRequest $request, Expense $expense): RedirectResponse
    {
        Gate::authorize('update', $expense);

        $data = $request->safe()->except(['attachment', 'branch_id']);
        if ($request->user()->canAccessAllBranches() && $request->filled('branch_id')) {
            $data['branch_id'] = $this->resolveBranchId($request);
        }

        $this->expenses->update($expense, $data, $request->user(), $request->file('attachment'));

        return redirect()->route('expenses.index')->with('success', 'Expense updated.');
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        Gate::authorize('delete', $expense);

        $this->expenses->delete($expense);

        return redirect()->route('expenses.index')->with('success', 'Expense deleted.');
    }

    private function formOptions(Request $request): array
    {
        return [
            'categories' => ExpenseCategory::where('is_active', true)->orderBy('name')->pluck('name', 'id'),
            'methods' => collect(PaymentMethod::cases())->mapWithKeys(fn ($m) => [$m->value => $m->label()]),
            'branches' => $request->user()->canAccessAllBranches() ? Branch::active()->orderBy('name')->pluck('name', 'id') : collect(),
        ];
    }
}
