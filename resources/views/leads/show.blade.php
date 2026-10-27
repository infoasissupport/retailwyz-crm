@extends('layouts.app', ['breadcrumbs' => ['Leads' => route('leads.index'), $lead->name => null]])

@section('title', $lead->name)

@section('page-actions')
    @can('convert', $lead)
        @unless ($lead->customer_id || $lead->status->value === 'lost')
            <form method="POST" action="{{ route('leads.convert', $lead) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-sm btn-success" data-confirm="Convert this lead into a customer?">Convert to customer</button>
            </form>
        @endunless
    @endcan
    @can('update', $lead)
        <a href="{{ route('leads.edit', $lead) }}" class="btn btn-sm btn-outline-primary">Edit</a>
    @endcan
    @can('delete', $lead)
        <form method="POST" action="{{ route('leads.destroy', $lead) }}" class="d-inline">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="Delete this lead?">Delete</button>
        </form>
    @endcan
@endsection

@section('content')
<div class="fw-grid gap-3">
    <div class="fw-span-lg-4">
        <section class="fw-card p-3">
            <div class="d-flex justify-content-between align-items-start">
                @include('leads.partials.status-badge', ['status' => $lead->status])
                <span class="small text-secondary">#{{ $lead->id }}</span>
            </div>

            @if ($lead->customer)
                <div class="alert alert-success py-2 small">
                    Converted {{ $lead->converted_at?->format('j M Y') }} to
                    <a href="{{ route('customers.show', $lead->customer_id) }}">{{ $lead->customer->name }} ({{ $lead->customer->code }})</a>
                </div>
            @endif

            <dl class="fw-grid small mb-0">
                <dt class="fw-span-5 text-secondary fw-normal">Phone</dt>
                <dd class="fw-span-7">@if ($lead->phone)<a href="tel:{{ $lead->phone }}">{{ $lead->phone }}</a>@else — @endif</dd>
                <dt class="fw-span-5 text-secondary fw-normal">Email</dt>
                <dd class="fw-span-7 text-break">@if ($lead->email)<a href="mailto:{{ $lead->email }}">{{ $lead->email }}</a>@else — @endif</dd>
                <dt class="fw-span-5 text-secondary fw-normal">Company</dt><dd class="fw-span-7">{{ $lead->company ?? '—' }}</dd>
                <dt class="fw-span-5 text-secondary fw-normal">Source</dt><dd class="fw-span-7">{{ $lead->source->label() }}</dd>
                <dt class="fw-span-5 text-secondary fw-normal">Interested in</dt><dd class="fw-span-7">{{ $lead->category?->name ?? '—' }}</dd>
                <dt class="fw-span-5 text-secondary fw-normal">Expected value</dt><dd class="fw-span-7">{{ $lead->expected_value ? money($lead->expected_value) : '—' }}</dd>
                <dt class="fw-span-5 text-secondary fw-normal">Assigned to</dt><dd class="fw-span-7">{{ $lead->assignee?->name ?? 'Unassigned' }}</dd>
                <dt class="fw-span-5 text-secondary fw-normal">Created</dt><dd class="fw-span-7">{{ $lead->created_at->format('j M Y') }} by {{ $lead->creator?->name ?? '—' }}</dd>
                @if ($lead->lost_reason)
                    <dt class="fw-span-5 text-secondary fw-normal">Lost reason</dt><dd class="fw-span-7">{{ $lead->lost_reason }}</dd>
                @endif
                @if ($lead->address)
                    <dt class="fw-span-5 text-secondary fw-normal">Address</dt><dd class="fw-span-7">{{ $lead->address }}</dd>
                @endif
            </dl>
        </section>

        @if ($lead->requirement || $lead->notes)
            <section class="fw-card p-3">
                @if ($lead->requirement)
                    <h2 class="fw-card-title mb-1">Requirement</h2>
                    <p class="small fw-pre-line">{{ $lead->requirement }}</p>
                @endif
                @if ($lead->notes)
                    <h2 class="fw-card-title mb-1">Notes</h2>
                    <p class="small mb-0 fw-pre-line">{{ $lead->notes }}</p>
                @endif
            </section>
        @endif
    </div>

    <div class="fw-span-lg-8">
        @can('update', $lead)
            @if ($lead->status->isOpen())
                <section class="fw-card p-3">
                    <h2 class="fw-card-title">Schedule a follow-up</h2>
                    <form method="POST" action="{{ route('leads.follow-ups.store', $lead) }}" class="fw-grid gap-2 align-items-end">
                        @csrf
                        <div class="fw-span-sm-4">
                            <label class="form-label small mb-1" for="fu-type">Type</label>
                            <select name="type" id="fu-type" class="form-select form-select-sm">
                                @foreach ($followUpTypes as $value => $label)
                                    <option value="{{ $value }}" @selected(old('type') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="fw-span-sm-4">
                            <label class="form-label small mb-1" for="fu-when">When</label>
                            <input type="datetime-local" name="scheduled_at" id="fu-when" value="{{ old('scheduled_at') }}" class="form-control form-control-sm" required>
                        </div>
                        <div class="fw-span-sm-4">
                            <label class="form-label small mb-1" for="fu-assignee">Assign to</label>
                            <select name="assigned_to" id="fu-assignee" class="form-select form-select-sm">
                                @foreach ($staff as $id => $name)
                                    <option value="{{ $id }}" @selected(($lead->assigned_to ?? auth()->id()) == $id)>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="fw-span-sm-9">
                            <input type="text" name="notes" value="{{ old('notes') }}" class="form-control form-control-sm" placeholder="Note (optional)" maxlength="2000">
                        </div>
                        <div class="fw-span-sm-3">
                            <button type="submit" class="btn btn-sm btn-primary w-100">Schedule</button>
                        </div>
                    </form>
                </section>
            @endif
        @endcan

        <section class="fw-card p-3">
            <h2 class="fw-card-title">Follow-up history</h2>
            <ol class="fw-timeline list-unstyled mb-0">
                @forelse ($lead->followUps as $followUp)
                    <li @class(['fw-timeline-item', 'is-pending' => $followUp->isPending(), 'is-overdue' => $followUp->isOverdue()])>
                        <div class="d-flex flex-wrap justify-content-between gap-2">
                            <div>
                                <span class="fw-semibold">{{ $followUp->type->label() }}</span>
                                <span class="text-secondary small">· {{ $followUp->scheduled_at->format('D, j M Y, g:i A') }}</span>
                                @if ($followUp->isOverdue())
                                    <span class="fw-status is-critical ms-1"><span aria-hidden="true">!</span> Overdue</span>
                                @elseif ($followUp->isPending())
                                    <span class="fw-status is-warning ms-1"><span aria-hidden="true">◷</span> Pending</span>
                                @elseif ($followUp->status === 'done')
                                    <span class="fw-status is-good ms-1"><span aria-hidden="true">✓</span> Done</span>
                                @else
                                    <span class="fw-chip ms-1">Cancelled</span>
                                @endif
                            </div>
                            <span class="small text-secondary">{{ $followUp->assignee?->name }}</span>
                        </div>
                        @if ($followUp->notes)
                            <p class="small mb-1 mt-1">{{ $followUp->notes }}</p>
                        @endif
                        @if ($followUp->outcome)
                            <p class="small mb-1 mt-1"><span class="text-secondary">Outcome:</span> {{ $followUp->outcome }}
                                <span class="text-secondary">— {{ $followUp->completer?->name }}, {{ $followUp->completed_at?->format('j M, g:i A') }}</span></p>
                        @endif
                        @if ($followUp->isPending())
                            @can('update', $followUp)
                                <div class="d-flex flex-wrap gap-2 mt-2 align-items-start">
                                    @include('leads.partials.complete-follow-up')
                                    <form method="POST" action="{{ route('leads.follow-ups.cancel', $followUp) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-link text-secondary" data-confirm="Cancel this follow-up?">Cancel</button>
                                    </form>
                                </div>
                            @endcan
                        @endif
                    </li>
                @empty
                    <li class="small text-secondary">No follow-ups yet.</li>
                @endforelse
            </ol>
        </section>
    </div>
</div>
@endsection
