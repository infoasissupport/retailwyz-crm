@extends('layouts.app', ['breadcrumbs' => ['Staff' => null, 'Employees' => null]])

@section('title', 'Employees')

@section('page-actions')
    @can('create', App\Models\Employee::class)
        <a href="{{ route('staff.employees.create') }}" class="btn btn-sm btn-primary">Add employee</a>
    @endcan
@endsection

@section('content')
    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-6">
            <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search name, code, phone or designation">
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select">
                <option value="">Active &amp; inactive</option>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3"><button type="submit" class="btn btn-outline-secondary w-100">Filter</button></div>
    </form>

    <div class="fw-card">
        <div class="table-responsive">
            <table class="table fw-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Designation</th>
                        <th>Phone</th>
                        <th>Branch</th>
                        <th>Login</th>
                        <th>Today</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($employees as $employee)
                        @php $mark = isset($todayMarks[$employee->id]) ? App\Enums\AttendanceStatus::from($todayMarks[$employee->id]) : null; @endphp
                        <tr>
                            <td>
                                <a href="{{ route('staff.employees.show', $employee) }}" class="fw-semibold text-decoration-none">{{ $employee->name }}</a>
                                <div class="small text-secondary">{{ $employee->employee_code }}</div>
                            </td>
                            <td class="small">{{ $employee->designation ?? '—' }}</td>
                            <td class="small">{{ $employee->phone ?? '—' }}</td>
                            <td class="small">{{ $employee->branch->name }}</td>
                            <td class="small">{{ $employee->user?->email ?? '—' }}</td>
                            <td>
                                @if ($mark)
                                    @include('staff.partials.attendance-badge', ['status' => $mark])
                                @else
                                    <span class="small text-secondary">Not marked</span>
                                @endif
                            </td>
                            <td>
                                @if ($employee->status === 'active')
                                    <span class="fw-status is-good"><span aria-hidden="true">●</span> Active</span>
                                @else
                                    <span class="fw-chip">{{ $statuses[$employee->status] ?? $employee->status }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-secondary py-4">No employees yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $employees->links() }}</div>
@endsection
