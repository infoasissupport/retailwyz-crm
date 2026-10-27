<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\StaffTarget;
use App\Services\StaffService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StaffTargetController extends Controller implements HasMiddleware
{
    public function __construct(private StaffService $staff) {}

    public static function middleware(): array
    {
        return [
            new Middleware('can:staff.view', only: ['index']),
            new Middleware('can:targets.manage', only: ['store', 'update', 'destroy']),
            new Middleware('can:incentives.manage', only: ['generateIncentives']),
        ];
    }

    public function index(Request $request): View
    {
        $month = $this->month($request);

        $targets = $this->targetsFor($request, $month)->get();
        $achieved = $this->staff->achievements($targets);

        return view('staff.targets.index', [
            'month' => $month,
            'targets' => $targets,
            'achieved' => $achieved,
            'earned' => $targets->mapWithKeys(fn ($t) => [$t->id => $this->staff->earnedIncentive($t, $achieved[$t->id])]),
            'employees' => Employee::query()->visibleTo($request->user())->active()
                ->whereNotIn('id', $targets->pluck('employee_id'))
                ->orderBy('name')->get(['id', 'name', 'employee_code', 'user_id']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request, creating: true);
        $employee = Employee::visibleTo($request->user())->findOrFail($data['employee_id']);
        $period = Carbon::parse($data['month'].'-01');

        if (StaffTarget::where('employee_id', $employee->id)->whereDate('period', $period)->exists()) {
            return back()->withInput()->with('error', "{$employee->name} already has a target for {$period->format('F Y')}.");
        }

        $target = new StaffTarget($data);
        $target->forceFill([
            'employee_id' => $employee->id,
            'branch_id' => $employee->branch_id,
            'period' => $period,
            'created_by' => $request->user()->id,
        ])->save();

        return redirect()->route('staff.targets.index', ['month' => $data['month']])->with('success', 'Target set.');
    }

    public function update(Request $request, StaffTarget $target): RedirectResponse
    {
        abort_unless($request->user()->canAccessBranch($target->branch_id), 403);
        $target->loadMissing('incentive');

        if ($target->incentive) {
            return back()->with('error', 'An incentive has already been raised for this target; it can no longer be changed.');
        }

        $target->update($this->validated($request, creating: false));

        return back()->with('success', 'Target updated.');
    }

    public function destroy(Request $request, StaffTarget $target): RedirectResponse
    {
        abort_unless($request->user()->canAccessBranch($target->branch_id), 403);

        if ($target->incentive()->exists()) {
            return back()->with('error', 'An incentive has already been raised for this target; it cannot be deleted.');
        }

        $target->delete();

        return back()->with('success', 'Target removed.');
    }

    public function generateIncentives(Request $request): RedirectResponse
    {
        $month = $this->month($request);
        $targets = $this->targetsFor($request, $month)->get();
        $created = $this->staff->generateTargetIncentives($targets, $request->user());

        return redirect()->route('staff.targets.index', ['month' => $month->format('Y-m')])
            ->with('success', $created ? "{$created} incentive(s) raised for approval." : 'No new incentives: no further targets were met.');
    }

    private function targetsFor(Request $request, Carbon $month)
    {
        return StaffTarget::query()
            ->visibleTo($request->user())
            ->with(['employee:id,name,employee_code,user_id,branch_id', 'incentive'])
            ->when($this->branchFilter($request), fn ($q, $id) => $q->where('branch_id', $id))
            ->whereDate('period', $month);
    }

    private function month(Request $request): Carbon
    {
        $value = $request->input('month');

        return (is_string($value) && preg_match('/^\d{4}-\d{2}$/', $value) ? Carbon::parse($value.'-01') : now())->startOfMonth()->startOfDay();
    }

    private function validated(Request $request, bool $creating): array
    {
        return $request->validate([
            'employee_id' => [$creating ? 'required' : 'prohibited', 'integer'],
            'month' => [$creating ? 'required' : 'prohibited', 'date_format:Y-m'],
            'target_amount' => ['required', 'decimal:0,2', 'gt:0', 'max:9999999999999'],
            'incentive_type' => ['required', Rule::in([StaffTarget::PERCENT, StaffTarget::FIXED])],
            'incentive_value' => ['required', 'decimal:0,2', 'min:0', $request->input('incentive_type') === StaffTarget::PERCENT ? 'max:100' : 'max:9999999999999'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
    }
}
