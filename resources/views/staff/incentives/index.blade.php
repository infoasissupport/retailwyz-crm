@extends('layouts.app', ['breadcrumbs' => ['Staff' => null, 'Incentives' => null]])

@section('title', 'Incentives')

@section('content')
    <div class="row g-3 mb-3">
        @foreach (['pending' => 'Awaiting approval', 'approved' => 'Approved, unpaid', 'paid' => 'Paid'] as $status => $label)
            <div class="col-sm-4">
                <a href="{{ route('staff.incentives.index', ['status' => $status]) }}" class="fw-card fw-tile">
                    <span class="fw-label">{{ $label }}</span>
                    <span class="fw-tile-value">{{ money($totals[$status]->total ?? 0) }}</span>
                    <span class="fw-tile-meta">{{ $totals[$status]->count ?? 0 }} {{ \Illuminate\Support\Str::plural('incentive', $totals[$status]->count ?? 0) }}</span>
                </a>
            </div>
        @endforeach
    </div>

    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-3">
            <select name="status" class="form-select">
                <option value="">All statuses</option>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <select name="employee_id" class="form-select">
                <option value="">All employees</option>
                @foreach ($employees as $id => $name)
                    <option value="{{ $id }}" @selected(request('employee_id') == $id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3"><input type="month" name="month" value="{{ request('month') }}" class="form-control"></div>
        <div class="col-md-2"><button type="submit" class="btn btn-outline-secondary w-100">Filter</button></div>
    </form>

    <div class="fw-card mb-3">
        <div class="table-responsive">
            <table class="table fw-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Month</th>
                        <th>Type</th>
                        <th class="text-end">Amount</th>
                        <th>Status</th>
                        <th>Raised / approved</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($incentives as $incentive)
                        <tr>
                            <td>
                                <a href="{{ route('staff.employees.show', $incentive->employee_id) }}" class="fw-semibold text-decoration-none">{{ $incentive->employee->name }}</a>
                                @if ($incentive->description)<div class="small text-secondary">{{ $incentive->description }}</div>@endif
                            </td>
                            <td>{{ $incentive->period->format('M Y') }}</td>
                            <td class="small">{{ $incentive->typeLabel() }}</td>
                            <td class="text-end fw-num fw-semibold">{{ money($incentive->amount) }}</td>
                            <td>
                                @include('staff.partials.incentive-badge', ['status' => $incentive->status])
                                @if ($incentive->expense)<div class="small text-secondary">{{ $incentive->expense->reference_no }}</div>@endif
                            </td>
                            <td class="small text-secondary">
                                {{ $incentive->creator?->name ?? 'System' }}
                                @if ($incentive->approver)<br>{{ $incentive->approver->name }}, {{ $incentive->approved_at?->format('j M') }}@endif
                            </td>
                            <td class="text-end">
                                @if ($incentive->status->value === 'pending')
                                    @can('approve', $incentive)
                                        <form method="POST" action="{{ route('staff.incentives.approve', $incentive) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                        </form>
                                        <form method="POST" action="{{ route('staff.incentives.reject', $incentive) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-link text-danger" data-confirm="Reject this incentive?">Reject</button>
                                        </form>
                                    @endcan
                                @elseif ($incentive->status->value === 'approved')
                                    @can('pay', $incentive)
                                        <details class="fw-complete d-inline-block text-start">
                                            <summary class="btn btn-sm btn-primary">Pay</summary>
                                            <form method="POST" action="{{ route('staff.incentives.pay', $incentive) }}" class="mt-2 p-3 border rounded bg-light" style="min-width: 260px">
                                                @csrf
                                                <label class="form-label small mb-1" for="pm-{{ $incentive->id }}">Method</label>
                                                <select name="payment_method" id="pm-{{ $incentive->id }}" class="form-select form-select-sm mb-2">
                                                    @foreach ($methods as $value => $label)
                                                        <option value="{{ $value }}">{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                                <label class="form-label small mb-1" for="pd-{{ $incentive->id }}">Paid on</label>
                                                <input type="date" name="paid_on" id="pd-{{ $incentive->id }}" value="{{ today()->toDateString() }}" max="{{ today()->toDateString() }}" class="form-control form-control-sm mb-2">
                                                <button type="submit" class="btn btn-sm btn-primary">Pay {{ money($incentive->amount) }}</button>
                                                <p class="small text-secondary mb-0 mt-2">Recorded as an expense under “Staff incentives”.</p>
                                            </form>
                                        </details>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-secondary py-4">No incentives found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mb-3">{{ $incentives->links() }}</div>

    @can('create', App\Models\Incentive::class)
        <section class="fw-card p-3" style="max-width: 960px">
            <h2 class="fw-card-title mb-3">Raise an incentive</h2>
            <form method="POST" action="{{ route('staff.incentives.store') }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-4">
                    <label class="form-label small mb-1" for="inc-employee">Employee</label>
                    <select name="employee_id" id="inc-employee" class="form-select form-select-sm" required>
                        <option value="">Select…</option>
                        @foreach ($employees as $id => $name)
                            <option value="{{ $id }}" @selected(old('employee_id') == $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1" for="inc-period">Month</label>
                    <input type="month" name="period" id="inc-period" value="{{ old('period', now()->format('Y-m')) }}" class="form-control form-control-sm" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1" for="inc-type">Type</label>
                    <select name="type" id="inc-type" class="form-select form-select-sm">
                        @foreach ($types as $value => $label)
                            <option value="{{ $value }}" @selected(old('type', 'performance') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1" for="inc-amount">Amount</label>
                    <input type="number" step="0.01" min="0.01" name="amount" id="inc-amount" value="{{ old('amount') }}" class="form-control form-control-sm" required>
                </div>
                <div class="col-md-9">
                    <input type="text" name="description" value="{{ old('description') }}" class="form-control form-control-sm" placeholder="Reason (optional)" maxlength="255">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-sm btn-primary w-100">Raise for approval</button>
                </div>
            </form>
        </section>
    @endcan
@endsection
