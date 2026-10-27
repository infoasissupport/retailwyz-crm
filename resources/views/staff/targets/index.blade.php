@extends('layouts.app', ['breadcrumbs' => ['Staff' => null, 'Targets' => null]])

@section('title', 'Sales targets')

@section('page-actions')
    @can('incentives.manage')
        <form method="POST" action="{{ route('staff.targets.generate-incentives', ['month' => $month->format('Y-m')]) }}">
            @csrf
            <button type="submit" class="btn btn-sm btn-outline-primary" data-confirm="Raise incentives for every met target in {{ $month->format('F Y') }}?">Raise incentives for met targets</button>
        </form>
    @endcan
@endsection

@section('content')
    @php
        $totalTarget = $targets->sum(fn ($t) => (float) $t->target_amount);
        $totalAchieved = array_sum(array_map('floatval', $achieved));
    @endphp

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <form method="GET" class="d-flex gap-2">
            <input type="month" name="month" value="{{ $month->format('Y-m') }}" class="form-control form-control-sm" style="width: auto" onchange="this.form.submit()">
        </form>
        <p class="small text-secondary mb-0">Achievement counts completed sales billed by the employee’s linked login, net of returns.</p>
    </div>

    <div class="fw-grid gap-3">
        <div class="fw-span-sm-4"><div class="fw-card fw-tile"><span class="fw-label">Team target</span><span class="fw-tile-value">{{ money($totalTarget) }}</span><span class="fw-tile-meta">{{ $targets->count() }} employees</span></div></div>
        <div class="fw-span-sm-4"><div class="fw-card fw-tile"><span class="fw-label">Achieved</span><span class="fw-tile-value">{{ money($totalAchieved) }}</span><span class="fw-tile-meta">{{ $totalTarget > 0 ? round($totalAchieved / $totalTarget * 100) : 0 }}% of target</span></div></div>
        <div class="fw-span-sm-4"><div class="fw-card fw-tile"><span class="fw-label">Targets met</span><span class="fw-tile-value">{{ $targets->filter(fn ($t) => (float) $achieved[$t->id] >= (float) $t->target_amount)->count() }}</span><span class="fw-tile-meta">of {{ $targets->count() }}</span></div></div>
    </div>

    <div class="fw-card">
        <div class="table-responsive">
            <table class="table fw-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th class="text-end">Target</th>
                        <th class="text-end">Achieved</th>
                        <th style="min-width: 160px">Progress</th>
                        <th>Incentive rule</th>
                        <th class="text-end">Earned</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($targets as $target)
                        @php
                            $pct = (float) $target->target_amount > 0 ? round((float) $achieved[$target->id] / (float) $target->target_amount * 100) : 0;
                            $met = $pct >= 100;
                        @endphp
                        <tr>
                            <td>
                                <a href="{{ route('staff.employees.show', $target->employee_id) }}" class="fw-semibold text-decoration-none">{{ $target->employee->name }}</a>
                                <div class="small text-secondary">{{ $target->employee->employee_code }}@unless ($target->employee->user_id) · <span class="text-danger">no login linked</span>@endunless</div>
                            </td>
                            <td class="text-end fw-num">{{ money($target->target_amount) }}</td>
                            <td class="text-end fw-num">{{ money($achieved[$target->id]) }}</td>
                            <td>
                                <div @class(['fw-meter mb-0']) aria-hidden="true"><span style="width: {{ min(100, $pct) }}%"></span></div>
                                <span class="small">{{ $pct }}%</span>
                                @if ($met)<span class="fw-status is-good ms-1"><span aria-hidden="true">✓</span> Met</span>@endif
                            </td>
                            <td class="small">{{ $target->incentive_type === 'percent' ? rtrim(rtrim($target->incentive_value, '0'), '.').'% of sales' : money($target->incentive_value).' fixed' }}</td>
                            <td class="text-end">
                                <span class="fw-num">{{ money($earned[$target->id]) }}</span>
                                @if ($target->incentive)
                                    <div>@include('staff.partials.incentive-badge', ['status' => $target->incentive->status])</div>
                                @endif
                            </td>
                            <td class="text-end">
                                @can('targets.manage')
                                    @unless ($target->incentive)
                                        <details class="fw-complete d-inline-block text-start">
                                            <summary class="btn btn-sm btn-link">Edit</summary>
                                            <form method="POST" action="{{ route('staff.targets.update', $target) }}" class="mt-2 p-3 border rounded bg-light">
                                                @csrf
                                                @method('PUT')
                                                @include('staff.targets.partials.rule-fields', ['target' => $target, 'idPrefix' => "t{$target->id}"])
                                                <div class="d-flex gap-2 mt-2">
                                                    <button type="submit" class="btn btn-sm btn-primary">Save</button>
                                                </div>
                                            </form>
                                        </details>
                                        <form method="POST" action="{{ route('staff.targets.destroy', $target) }}" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-link text-danger" data-confirm="Remove this target?">Remove</button>
                                        </form>
                                    @endunless
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-secondary py-4">No targets for {{ $month->format('F Y') }}.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @can('targets.manage')
        @if ($employees->isNotEmpty())
            <section class="fw-card p-3" style="max-width: 960px">
                <h2 class="fw-card-title">Set a target for {{ $month->format('F Y') }}</h2>
                <form method="POST" action="{{ route('staff.targets.store') }}">
                    @csrf
                    <input type="hidden" name="month" value="{{ $month->format('Y-m') }}">
                    <div class="fw-grid gap-2">
                        <div class="fw-span-md-4">
                            <label class="form-label small mb-1" for="new-employee">Employee</label>
                            <select name="employee_id" id="new-employee" class="form-select form-select-sm" required>
                                <option value="">Select…</option>
                                @foreach ($employees as $employee)
                                    <option value="{{ $employee->id }}" @selected(old('employee_id') == $employee->id)>{{ $employee->name }} ({{ $employee->employee_code }}){{ $employee->user_id ? '' : ' — no login' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="fw-span-md-8">
                            @include('staff.targets.partials.rule-fields', ['target' => null, 'idPrefix' => 'new'])
                        </div>
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary mt-3">Set target</button>
                </form>
            </section>
        @endif
    @endcan
@endsection
