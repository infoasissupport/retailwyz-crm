@extends('layouts.app', ['breadcrumbs' => ['Staff' => null, 'Attendance' => null]])

@section('title', 'Attendance')

@section('page-actions')
    <a href="{{ route('staff.attendance.register', ['month' => $date->format('Y-m'), 'branch_id' => request('branch_id')]) }}" class="btn btn-sm btn-outline-secondary">Monthly register</a>
@endsection

@section('content')
    @php $canMark = auth()->user()->can('attendance.manage'); @endphp

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <form method="GET" class="d-flex flex-wrap align-items-center gap-2">
            <a href="{{ route('staff.attendance.index', ['date' => $date->copy()->subDay()->toDateString(), 'branch_id' => request('branch_id')]) }}" class="btn btn-sm btn-outline-secondary" aria-label="Previous day">‹</a>
            <input type="date" name="date" value="{{ $date->toDateString() }}" max="{{ today()->toDateString() }}" class="form-control form-control-sm" style="width: auto" onchange="this.form.submit()">
            @if ($date->lt(today()))
                <a href="{{ route('staff.attendance.index', ['date' => $date->copy()->addDay()->toDateString(), 'branch_id' => request('branch_id')]) }}" class="btn btn-sm btn-outline-secondary" aria-label="Next day">›</a>
            @endif
            @if (auth()->user()->canAccessAllBranches())
                <select name="branch_id" class="form-select form-select-sm" style="width: auto" onchange="this.form.submit()">
                    @foreach (App\Models\Branch::active()->orderBy('name')->pluck('name', 'id') as $id => $name)
                        <option value="{{ $id }}" @selected($branchId == $id)>{{ $name }}</option>
                    @endforeach
                </select>
            @endif
            <span class="fw-semibold ms-1">{{ $date->format('l, j F Y') }}</span>
        </form>

        <div class="d-flex flex-wrap gap-2">
            @foreach ($summary as $label => $count)
                <span class="fw-chip">{{ $label }}: {{ $count }}</span>
            @endforeach
        </div>
    </div>

    <form method="POST" action="{{ route('staff.attendance.store') }}">
        @csrf
        <input type="hidden" name="date" value="{{ $date->toDateString() }}">
        <input type="hidden" name="branch_id" value="{{ $branchId }}">

        <div class="fw-card">
            <div class="table-responsive">
                <table class="table fw-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Status</th>
                            <th>In</th>
                            <th>Out</th>
                            <th>Note</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($employees as $employee)
                            @php
                                $mark = $marks->get($employee->id);
                                $current = old("rows.{$employee->id}.status", $mark?->status->value);
                            @endphp
                            <tr>
                                <td>
                                    <span class="fw-semibold">{{ $employee->name }}</span>
                                    <div class="small text-secondary">{{ $employee->employee_code }} · {{ $employee->designation }}</div>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm fw-att-group" role="radiogroup" aria-label="Attendance for {{ $employee->name }}">
                                        @foreach ($statuses as $status)
                                            <input type="radio" class="btn-check" name="rows[{{ $employee->id }}][status]" value="{{ $status->value }}"
                                                   id="att-{{ $employee->id }}-{{ $status->value }}" @checked($current === $status->value) @disabled(! $canMark)>
                                            <label class="btn btn-outline-secondary fw-att-{{ $status->value }}" for="att-{{ $employee->id }}-{{ $status->value }}" title="{{ $status->label() }}">
                                                {{ $status->code() }}<span class="d-none d-xl-inline"> {{ $status->label() }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </td>
                                <td><input type="time" name="rows[{{ $employee->id }}][check_in]" value="{{ old("rows.{$employee->id}.check_in", $mark?->check_in ? substr($mark->check_in, 0, 5) : '') }}" class="form-control form-control-sm" @disabled(! $canMark)></td>
                                <td><input type="time" name="rows[{{ $employee->id }}][check_out]" value="{{ old("rows.{$employee->id}.check_out", $mark?->check_out ? substr($mark->check_out, 0, 5) : '') }}" class="form-control form-control-sm" @disabled(! $canMark)></td>
                                <td><input type="text" name="rows[{{ $employee->id }}][notes]" value="{{ old("rows.{$employee->id}.notes", $mark?->notes) }}" class="form-control form-control-sm" maxlength="255" @disabled(! $canMark)></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-secondary py-4">
                                    No active employees in this branch.
                                    @can('create', App\Models\Employee::class)<a href="{{ route('staff.employees.create') }}">Add an employee</a>.@endcan
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($canMark && $employees->isNotEmpty())
            <div class="d-flex flex-wrap gap-2 mt-3">
                <button type="submit" class="btn btn-primary">Save attendance</button>
                <button type="button" class="btn btn-outline-secondary" data-fw-mark-all="present">Mark unmarked as present</button>
            </div>
        @endif
    </form>
@endsection

@push('scripts')
<script>
    document.querySelector('[data-fw-mark-all]')?.addEventListener('click', (event) => {
        const status = event.currentTarget.dataset.fwMarkAll;
        document.querySelectorAll('.fw-att-group').forEach((group) => {
            if (!group.querySelector('input:checked')) {
                group.querySelector(`input[value="${status}"]`).checked = true;
            }
        });
    });
</script>
@endpush
