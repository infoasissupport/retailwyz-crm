<?php

namespace App\Http\Controllers\Staff;

use App\Enums\AttendanceStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\EmployeeRequest;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\User;
use App\Services\StaffService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function __construct(private StaffService $staff) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Employee::class);

        $employees = Employee::query()
            ->visibleTo($request->user())
            ->with(['branch:id,name', 'user:id,email'])
            ->when($this->branchFilter($request), fn ($q, $id) => $q->where('branch_id', $id))
            ->search($request->string('q')->trim()->toString())
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')), fn ($q) => $q->where('status', '!=', 'resigned'))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('staff.employees.index', [
            'employees' => $employees,
            'statuses' => Employee::STATUSES,
            'todayMarks' => DB::table('attendances')->whereIn('employee_id', $employees->pluck('id'))->whereDate('date', today())->pluck('status', 'employee_id'),
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Employee::class);

        return view('staff.employees.create', ['employee' => new Employee(['status' => 'active'])] + $this->formOptions($request->user()));
    }

    public function store(EmployeeRequest $request): RedirectResponse
    {
        Gate::authorize('create', Employee::class);
        $data = $request->validated();
        $data['branch_id'] = $this->resolveBranchId($request);

        $employee = DB::transaction(function () use ($data) {
            $generate = empty($data['employee_code']);
            // Placeholder keeps the unique column valid until the id-based code is known.
            $employee = Employee::create(['employee_code' => $generate ? 'TMP-'.uniqid() : $data['employee_code']] + $data);
            if ($generate) {
                $employee->update(['employee_code' => 'EMP-'.str_pad((string) $employee->id, 4, '0', STR_PAD_LEFT)]);
            }

            return $employee;
        });

        return redirect()->route('staff.employees.show', $employee)->with('success', 'Employee added.');
    }

    public function show(Request $request, Employee $employee): View
    {
        Gate::authorize('view', $employee);
        $employee->load(['branch:id,name', 'user:id,name,email']);

        $month = $request->date('month', 'Y-m') ?? now();
        $attendance = $employee->attendances()
            ->whereBetween('date', [$month->copy()->startOfMonth()->toDateString(), $month->copy()->endOfMonth()->toDateString().' 23:59:59'])
            ->orderBy('date')
            ->get();

        $summary = collect(AttendanceStatus::cases())->mapWithKeys(fn ($s) => [$s->label() => $attendance->where('status', $s)->count()]);

        $targets = $employee->targets()->with(['employee', 'incentive'])->latest('period')->limit(6)->get();

        return view('staff.employees.show', [
            'employee' => $employee,
            'month' => $month,
            'attendance' => $attendance,
            'summary' => $summary,
            'workedDays' => $attendance->sum(fn ($a) => $a->status->credit()),
            'targets' => $targets,
            'achieved' => $this->staff->achievements($targets),
            'incentives' => $employee->incentives()->latest('period')->limit(10)->get(),
        ]);
    }

    public function edit(Request $request, Employee $employee): View
    {
        Gate::authorize('update', $employee);

        return view('staff.employees.edit', ['employee' => $employee] + $this->formOptions($request->user()));
    }

    public function update(EmployeeRequest $request, Employee $employee): RedirectResponse
    {
        Gate::authorize('update', $employee);
        $data = $request->validated();
        if (empty($data['employee_code'])) {
            unset($data['employee_code']);
        }

        $employee->update($data);

        return redirect()->route('staff.employees.show', $employee)->with('success', 'Employee updated.');
    }

    /** Soft delete; attendance and incentive history stays. The login link is released. */
    public function destroy(Employee $employee): RedirectResponse
    {
        Gate::authorize('delete', $employee);

        $employee->forceFill(['user_id' => null, 'status' => 'resigned'])->save();
        $employee->delete();

        return redirect()->route('staff.employees.index')->with('success', 'Employee removed.');
    }

    private function formOptions(User $user): array
    {
        $linked = Employee::withTrashed()->whereNotNull('user_id')->pluck('user_id');

        return [
            'statuses' => Employee::STATUSES,
            'branches' => $user->canAccessAllBranches() ? Branch::active()->orderBy('name')->pluck('name', 'id') : collect(),
            'users' => User::query()
                ->where('is_active', true)
                ->when(! $user->canAccessAllBranches(), fn ($q) => $q->where('branch_id', $user->branch_id))
                ->orderBy('name')
                ->get(['id', 'name', 'email'])
                ->mapWithKeys(fn ($u) => [$u->id => "{$u->name} ({$u->email})".($linked->contains($u->id) ? ' — linked' : '')]),
        ];
    }
}
