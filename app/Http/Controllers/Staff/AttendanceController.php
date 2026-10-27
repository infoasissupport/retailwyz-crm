<?php

namespace App\Http\Controllers\Staff;

use App\Enums\AttendanceStatus;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
use App\Services\StaffService;
use Carbon\CarbonPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AttendanceController extends Controller implements HasMiddleware
{
    public function __construct(private StaffService $staff) {}

    public static function middleware(): array
    {
        return [
            new Middleware('can:staff.view', only: ['index', 'register']),
            new Middleware('can:attendance.manage', only: ['store']),
        ];
    }

    /** Daily marking sheet for one branch. */
    public function index(Request $request): View
    {
        $date = $request->date('date') ?? today();
        $branchId = $this->sheetBranch($request);

        $employees = Employee::query()
            ->where('branch_id', $branchId)
            ->where(fn ($q) => $q->where('status', 'active')->orWhereHas('attendances', fn ($q) => $q->whereDate('date', $date)))
            ->orderBy('name')
            ->get();

        $marks = Attendance::query()
            ->where('branch_id', $branchId)
            ->whereDate('date', $date)
            ->get()
            ->keyBy('employee_id');

        return view('staff.attendance.index', [
            'date' => $date,
            'branchId' => $branchId,
            'employees' => $employees,
            'marks' => $marks,
            'statuses' => AttendanceStatus::cases(),
            'summary' => collect(AttendanceStatus::cases())->mapWithKeys(fn ($s) => [$s->label() => $marks->where('status', $s)->count()]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date', 'before_or_equal:today'],
            'rows' => ['required', 'array'],
            'rows.*.status' => ['nullable', Rule::enum(AttendanceStatus::class)],
            'rows.*.check_in' => ['nullable', 'date_format:H:i'],
            'rows.*.check_out' => ['nullable', 'date_format:H:i'],
            'rows.*.notes' => ['nullable', 'string', 'max:255'],
        ]);

        $saved = $this->staff->saveAttendance($this->sheetBranch($request), \Carbon\Carbon::parse($data['date']), $data['rows'], $request->user());

        return redirect()
            ->route('staff.attendance.index', ['date' => $data['date'], 'branch_id' => $request->input('branch_id')])
            ->with('success', "Attendance saved for {$saved} ".\Illuminate\Support\Str::plural('employee', $saved).'.');
    }

    /** Monthly register: one row per employee, one column per day. */
    public function register(Request $request): View
    {
        $month = ($request->date('month', 'Y-m') ?? now())->startOfMonth();
        $branchId = $this->sheetBranch($request);
        $days = collect(CarbonPeriod::create($month, $month->copy()->endOfMonth()));

        $employees = Employee::query()
            ->where('branch_id', $branchId)
            ->with(['attendances' => fn ($q) => $q->whereBetween('date', [$month->toDateString(), $month->copy()->endOfMonth()->toDateString().' 23:59:59'])])
            ->where(fn ($q) => $q->where('status', 'active')->orWhereHas('attendances', fn ($q) => $q->whereBetween('date', [$month->toDateString(), $month->copy()->endOfMonth()->toDateString().' 23:59:59'])))
            ->orderBy('name')
            ->get();

        return view('staff.attendance.register', compact('month', 'days', 'employees', 'branchId'));
    }

    /** Own branch, or a chosen branch for all-branch users (defaults to their own). */
    private function sheetBranch(Request $request): int
    {
        $user = $request->user();

        if ($user->canAccessAllBranches() && $request->integer('branch_id')) {
            return $request->integer('branch_id');
        }

        abort_if($user->branch_id === null, 403, 'Your account is not assigned to a branch.');

        return $user->branch_id;
    }
}
