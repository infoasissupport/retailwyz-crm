@extends('layouts.app', ['breadcrumbs' => ['Attendance' => route('staff.attendance.index'), 'Monthly register' => null]])

@section('title', 'Attendance register')

@section('content')
    <form method="GET" class="d-flex flex-wrap align-items-center gap-2">
        <input type="month" name="month" value="{{ $month->format('Y-m') }}" max="{{ now()->format('Y-m') }}" class="form-control form-control-sm fw-w-auto">
        @if (auth()->user()->canAccessAllBranches())
            <select name="branch_id" class="form-select form-select-sm fw-w-auto">
                @foreach (App\Models\Branch::active()->orderBy('name')->pluck('name', 'id') as $id => $name)
                    <option value="{{ $id }}" @selected($branchId == $id)>{{ $name }}</option>
                @endforeach
            </select>
        @endif
        <button type="submit" class="btn btn-sm btn-outline-secondary">Show</button>
        <span class="small text-secondary ms-2">P present · H half day · A absent · L leave · O holiday/off</span>
    </form>

    <div class="fw-card">
        <div class="table-responsive">
            <table class="table fw-table fw-register mb-0">
                <thead>
                    <tr>
                        <th class="fw-register-name">Employee</th>
                        @foreach ($days as $day)
                            <th @class(['text-center', 'fw-register-weekend' => $day->isSunday()])>
                                {{ $day->format('j') }}<div class="fw-normal">{{ $day->format('D')[0] }}</div>
                            </th>
                        @endforeach
                        <th class="text-end">Days</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($employees as $employee)
                        @php $byDate = $employee->attendances->keyBy(fn ($a) => $a->date->toDateString()); @endphp
                        <tr>
                            <td class="fw-register-name">
                                <a href="{{ route('staff.employees.show', ['employee' => $employee, 'month' => $month->format('Y-m')]) }}" class="text-decoration-none">{{ $employee->name }}</a>
                            </td>
                            @foreach ($days as $day)
                                @php $mark = $byDate->get($day->toDateString()); @endphp
                                <td @class(['text-center', 'fw-register-weekend' => $day->isSunday()]) @if ($mark) title="{{ $mark->status->label() }}" @endif>
                                    @if ($mark)
                                        <span class="fw-reg-{{ $mark->status->value }}">{{ $mark->status->code() }}</span>
                                    @else
                                        <span class="text-secondary">·</span>
                                    @endif
                                </td>
                            @endforeach
                            <td class="text-end fw-semibold">{{ rtrim(rtrim(number_format($employee->attendances->sum(fn ($a) => $a->status->credit()), 1), '0'), '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $days->count() + 2 }}" class="text-center text-secondary py-4">No employees.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
