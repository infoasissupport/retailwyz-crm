@extends('layouts.app', ['breadcrumbs' => ['Leads' => route('leads.index'), 'Follow-ups' => null]])

@section('title', 'Follow-ups')

@section('content')
    @php
        $tabs = [
            'due' => ['Due now', $counts['overdue'] + $counts['today']],
            'overdue' => ['Overdue', $counts['overdue']],
            'today' => ['Today', $counts['today']],
            'upcoming' => ['Upcoming', $counts['upcoming']],
            'done' => ['Completed', null],
        ];
    @endphp

    <div class="d-flex flex-wrap justify-content-between gap-2">
        <ul class="nav nav-pills gap-1">
            @foreach ($tabs as $key => [$label, $count])
                <li class="nav-item">
                    <a href="{{ route('leads.follow-ups.index', array_merge(request()->except('page'), ['view' => $key])) }}"
                       @class(['nav-link py-1 px-3', 'active' => $view === $key])>
                        {{ $label }}
                        @if ($count !== null)
                            <span @class(['badge ms-1', 'text-bg-danger' => $key === 'overdue' && $count > 0, 'text-bg-light' => ! ($key === 'overdue' && $count > 0)])>{{ $count }}</span>
                        @endif
                    </a>
                </li>
            @endforeach
        </ul>

        <form method="GET" class="d-flex align-items-center gap-2">
            <input type="hidden" name="view" value="{{ $view }}">
            <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" role="switch" id="mine" name="mine" value="1" @checked(request()->boolean('mine')) onchange="this.form.submit()">
                <label class="form-check-label small" for="mine">Only mine</label>
            </div>
        </form>
    </div>

    <div class="fw-card">
        <div class="table-responsive">
            <table class="table fw-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>When</th>
                        <th>Lead</th>
                        <th>Type</th>
                        <th>Notes</th>
                        <th>Assigned to</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($followUps as $followUp)
                        <tr>
                            <td>
                                <span @class(['fw-semibold', 'text-danger' => $followUp->isOverdue()])>{{ $followUp->scheduled_at->format('j M, g:i A') }}</span>
                                <div class="small text-secondary">
                                    @if ($followUp->status === 'done')
                                        Done {{ $followUp->completed_at?->diffForHumans() }}
                                    @else
                                        {{ $followUp->scheduled_at->diffForHumans() }}
                                    @endif
                                </div>
                            </td>
                            <td>
                                <a href="{{ route('leads.show', $followUp->lead_id) }}" class="fw-semibold text-decoration-none">{{ $followUp->lead->name }}</a>
                                <div class="small text-secondary">
                                    @if ($followUp->lead->phone)<a href="tel:{{ $followUp->lead->phone }}" class="text-secondary">{{ $followUp->lead->phone }}</a> · @endif
                                    {{ $followUp->lead->status->label() }}
                                </div>
                            </td>
                            <td class="small">{{ $followUp->type->label() }}</td>
                            <td class="small text-wrap" style="min-width: 200px; max-width: 320px">
                                {{ $followUp->status === 'done' ? $followUp->outcome : $followUp->notes }}
                            </td>
                            <td class="small">{{ $followUp->assignee?->name ?? '—' }}</td>
                            <td class="text-end">
                                @if ($followUp->isPending())
                                    @can('update', $followUp)
                                        <div class="text-start d-inline-block">@include('leads.partials.complete-follow-up')</div>
                                    @endcan
                                @else
                                    <span class="fw-status is-good"><span aria-hidden="true">✓</span> Done</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-secondary py-4">Nothing here. You're all caught up.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $followUps->links() }}</div>
@endsection
