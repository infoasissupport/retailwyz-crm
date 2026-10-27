<?php

namespace App\Http\Controllers\Staff;

use App\Enums\IncentiveStatus;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Incentive;
use App\Services\StaffService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class IncentiveController extends Controller
{
    public function __construct(private StaffService $staff) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Incentive::class);
        $user = $request->user();

        $base = fn () => Incentive::query()
            ->visibleTo($user)
            ->when($this->branchFilter($request), fn ($q, $id) => $q->where('branch_id', $id));

        $incentives = $base()
            ->with(['employee:id,name,employee_code,user_id', 'creator:id,name', 'approver:id,name', 'expense:id,reference_no'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->integer('employee_id'), fn ($q, $id) => $q->where('employee_id', $id))
            ->when($request->date('month', 'Y-m'), fn ($q, $m) => $q->whereDate('period', $m->startOfMonth()))
            ->latest('period')
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        $totals = $base()->selectRaw('status, COUNT(*) as count, COALESCE(SUM(amount), 0) as total')->groupBy('status')->get()->keyBy('status');

        return view('staff.incentives.index', [
            'incentives' => $incentives,
            'totals' => $totals,
            'statuses' => collect(IncentiveStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()]),
            'types' => Incentive::TYPES,
            'employees' => Employee::query()->visibleTo($user)->active()->orderBy('name')->pluck('name', 'id'),
            'methods' => collect(PaymentMethod::cases())->mapWithKeys(fn ($m) => [$m->value => $m->label()]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Incentive::class);

        $data = $request->validate([
            'employee_id' => ['required', 'integer'],
            'period' => ['required', 'date_format:Y-m'],
            'type' => ['required', Rule::in(array_keys(Incentive::TYPES))],
            'amount' => ['required', 'decimal:0,2', 'gt:0', 'max:9999999999999'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $employee = Employee::visibleTo($request->user())->findOrFail($data['employee_id']);
        $this->staff->createIncentive($employee, ['period' => $data['period'].'-01'] + $data, $request->user());

        return back()->with('success', 'Incentive raised for approval.');
    }

    public function approve(Incentive $incentive, Request $request): RedirectResponse
    {
        Gate::authorize('approve', $incentive);
        $this->staff->approve($incentive, $request->user());

        return back()->with('success', 'Incentive approved.');
    }

    public function reject(Incentive $incentive, Request $request): RedirectResponse
    {
        Gate::authorize('approve', $incentive);
        $this->staff->approve($incentive, $request->user(), approve: false);

        return back()->with('success', 'Incentive rejected.');
    }

    public function pay(Incentive $incentive, Request $request): RedirectResponse
    {
        Gate::authorize('pay', $incentive);

        $data = $request->validate([
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'paid_on' => ['required', 'date', 'before_or_equal:today'],
        ]);

        $this->staff->pay($incentive, $data, $request->user());

        return back()->with('success', 'Incentive paid and recorded as an expense.');
    }
}
