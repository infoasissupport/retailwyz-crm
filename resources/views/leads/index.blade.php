@extends('layouts.app', ['breadcrumbs' => ['Leads' => null]])

@section('title', 'All Leads')

@section('page-actions')
    @can('create', App\Models\Lead::class)
        <a href="{{ route('leads.create') }}" class="btn btn-sm btn-primary">New lead</a>
    @endcan
@endsection

@section('content')
    {{-- Pipeline summary: one link per status --}}
    <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="{{ route('leads.index', request()->except('status', 'page')) }}"
           @class(['btn btn-sm', 'btn-dark' => ! request('status'), 'btn-outline-secondary' => request('status')])>
            All <span class="ms-1">{{ $statusCounts->sum() }}</span>
        </a>
        @foreach ($statuses as $value => $label)
            <a href="{{ route('leads.index', array_merge(request()->except('page'), ['status' => $value])) }}"
               @class(['btn btn-sm', 'btn-dark' => request('status') === $value, 'btn-outline-secondary' => request('status') !== $value])>
                {{ $label }} <span class="ms-1">{{ $statusCounts[$value] ?? 0 }}</span>
            </a>
        @endforeach
    </div>

    <form method="GET" class="row g-2 mb-3">
        <input type="hidden" name="status" value="{{ request('status') }}">
        <div class="col-md-4">
            <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search name, phone, email or company">
        </div>
        <div class="col-md-3">
            <select name="source" class="form-select">
                <option value="">All sources</option>
                @foreach ($sources as $value => $label)
                    <option value="{{ $value }}" @selected(request('source') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <select name="assigned_to" class="form-select">
                <option value="">Anyone</option>
                @foreach ($staff as $id => $name)
                    <option value="{{ $id }}" @selected(request('assigned_to') == $id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-outline-secondary w-100">Filter</button>
        </div>
    </form>

    <div class="fw-card">
        <div class="table-responsive">
            <table class="table fw-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Lead</th>
                        <th>Interested in</th>
                        <th>Source</th>
                        <th class="text-end">Expected value</th>
                        <th>Status</th>
                        <th>Next follow-up</th>
                        <th>Assigned to</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($leads as $lead)
                        <tr>
                            <td>
                                <a href="{{ route('leads.show', $lead) }}" class="fw-semibold text-decoration-none">{{ $lead->name }}</a>
                                <div class="small text-secondary">{{ $lead->phone ?? $lead->email }}@if ($lead->company) · {{ $lead->company }}@endif</div>
                            </td>
                            <td class="small">{{ $lead->category?->name ?? \Illuminate\Support\Str::limit($lead->requirement, 30) ?: '—' }}</td>
                            <td class="small">{{ $lead->source->label() }}</td>
                            <td class="text-end fw-num">{{ $lead->expected_value ? money($lead->expected_value) : '—' }}</td>
                            <td>@include('leads.partials.status-badge', ['status' => $lead->status])</td>
                            <td class="small">
                                @if ($next = $lead->nextFollowUp)
                                    <span @class(['text-danger fw-semibold' => $next->isOverdue()])>
                                        {{ $next->scheduled_at->format('j M, g:i A') }}
                                    </span>
                                    <div class="text-secondary">{{ $next->type->label() }}@if ($next->isOverdue()) · overdue @endif</div>
                                @else
                                    <span class="text-secondary">—</span>
                                @endif
                            </td>
                            <td class="small">{{ $lead->assignee?->name ?? 'Unassigned' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-secondary py-4">
                                No leads found.
                                @can('create', App\Models\Lead::class)<a href="{{ route('leads.create') }}">Add the first lead</a>.@endcan
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $leads->links() }}</div>
@endsection
