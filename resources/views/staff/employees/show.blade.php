@extends('layouts.app', ['breadcrumbs' => ['Employees' => route('staff.employees.index'), $employee->name => null]])

@section('title', $employee->name)

@section('page-actions')
    @can('update', $employee)
        <a href="{{ route('staff.employees.edit', $employee) }}" class="btn btn-sm btn-outline-primary">Edit</a>
    @endcan
    @can('delete', $employee)
        <form method="POST" action="{{ route('staff.employees.destroy', $employee) }}" class="d-inline">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="Remove this employee? Their history is kept.">Remove</button>
        </form>
    @endcan
@endsection

@section('content')
<div class="fw-grid gap-3">
    <div class="fw-span-lg-4">
        <section class="fw-card p-3">
            <p class="fw-label mb-1">{{ $employee->employee_code }}</p>
            <p class="h5 mb-1">{{ $employee->name }}</p>
            <p class="text-secondary small">{{ $employee->designation ?? 'No designation' }} · {{ $employee->branch->name }}</p>

            <dl class="fw-grid small mb-0">
                <dt class="fw-span-5 text-secondary fw-normal">Status</dt><dd class="fw-span-7">{{ App\Models\Employee::STATUSES[$employee->status] ?? $employee->status }}</dd>
                <dt class="fw-span-5 text-secondary fw-normal">Phone</dt><dd class="fw-span-7">@if ($employee->phone)<a href="tel:{{ $employee->phone }}">{{ $employee->phone }}</a>@else — @endif</dd>
                <dt class="fw-span-5 text-secondary fw-normal">Email</dt><dd class="fw-span-7 text-break">{{ $employee->email ?? '—' }}</dd>
                <dt class="fw-span-5 text-secondary fw-normal">Joined</dt><dd class="fw-span-7">{{ $employee->joining_date?->format('j M Y') ?? '—' }}</dd>
                @can('staff.manage')
                    <dt class="fw-span-5 text-secondary fw-normal">Monthly salary</dt><dd class="fw-span-7">{{ $employee->monthly_salary ? money($employee->monthly_salary) : '—' }}</dd>
                @endcan
                <dt class="fw-span-5 text-secondary fw-normal">Login</dt><dd class="fw-span-7 text-break">{{ $employee->user?->email ?? 'Not linked' }}</dd>
                <dt class="fw-span-5 text-secondary fw-normal">Emergency</dt><dd class="fw-span-7">{{ $employee->emergency_contact ?? '—' }}</dd>
                @if ($employee->address)
                    <dt class="fw-span-5 text-secondary fw-normal">Address</dt><dd class="fw-span-7">{{ $employee->address }}</dd>
                @endif
            </dl>
        </section>
    </div>

    <div class="fw-span-lg-8">
        <section class="fw-card p-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h2 class="fw-card-title">Attendance · {{ $month->format('F Y') }}</h2>
                <form method="GET" class="d-flex gap-2">
                    <input type="month" name="month" value="{{ $month->format('Y-m') }}" class="form-control form-control-sm" max="{{ now()->format('Y-m') }}">
                    <button type="submit" class="btn btn-sm btn-outline-secondary">Go</button>
                </form>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <span class="fw-chip"><strong>{{ rtrim(rtrim(number_format($workedDays, 1), '0'), '.') }}</strong> working days</span>
                @foreach ($summary as $label => $count)
                    <span class="fw-chip">{{ $label }}: {{ $count }}</span>
                @endforeach
            </div>
            <div class="d-flex flex-wrap gap-1">
                @foreach ($attendance as $day)
                    <span class="fw-day fw-day-{{ $day->status->value }}" title="{{ $day->date->format('D j M') }}: {{ $day->status->label() }}">
                        <span class="small">{{ $day->date->format('j') }}</span>
                        <strong>{{ $day->status->code() }}</strong>
                    </span>
                @endforeach
                @if ($attendance->isEmpty())
                    <span class="small text-secondary">No attendance marked this month.</span>
                @endif
            </div>
        </section>

        <section class="fw-card">
            <h2 class="fw-card-title p-3 pb-2">Targets</h2>
            <div class="table-responsive">
                <table class="table fw-table mb-0">
                    <thead><tr><th>Month</th><th class="text-end">Target</th><th class="text-end">Achieved</th><th>Progress</th></tr></thead>
                    <tbody>
                        @forelse ($targets as $target)
                            @php $pct = (float) $target->target_amount > 0 ? round((float) $achieved[$target->id] / (float) $target->target_amount * 100) : 0; @endphp
                            <tr>
                                <td>{{ $target->period->format('M Y') }}</td>
                                <td class="text-end fw-num">{{ money($target->target_amount) }}</td>
                                <td class="text-end fw-num">{{ money($achieved[$target->id]) }}</td>
                                <td style="min-width: 140px">
                                    <div class="fw-meter mb-0"><span style="width: {{ min(100, $pct) }}%"></span></div>
                                    <span class="small text-secondary">{{ $pct }}%</span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-secondary small">No targets set.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="fw-card">
            <h2 class="fw-card-title p-3 pb-2">Incentives</h2>
            <div class="table-responsive">
                <table class="table fw-table mb-0">
                    <thead><tr><th>Month</th><th>Type</th><th class="text-end">Amount</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse ($incentives as $incentive)
                            <tr>
                                <td>{{ $incentive->period->format('M Y') }}</td>
                                <td>{{ $incentive->typeLabel() }}</td>
                                <td class="text-end fw-num">{{ money($incentive->amount) }}</td>
                                <td>@include('staff.partials.incentive-badge', ['status' => $incentive->status])</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-secondary small">No incentives yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
@endsection
