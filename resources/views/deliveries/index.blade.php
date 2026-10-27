@extends('layouts.app', ['breadcrumbs' => ['Deliveries' => null]])

@section('title', 'Deliveries')

@section('content')
    @php
        $tiles = [
            'pending' => ['Pending', 'is-pending'],
            'out_for_delivery' => ['Out for Delivery', 'is-transit'],
            'delivered' => ['Delivered', 'is-delivered'],
        ];
        $filters = ['' => 'All', 'pending' => 'Pending', 'out_for_delivery' => 'Out For Delivery', 'delivered' => 'Delivered', 'failed' => 'Failed', 'cancelled' => 'Cancelled'];
    @endphp

    <div class="row g-3 mb-3">
        @foreach ($tiles as $key => [$label, $tone])
            <div class="col-6 col-lg-3">
                <a href="{{ route('deliveries.index', ['status' => $key]) }}" class="fw-del-tile {{ $tone }}">
                    <span class="fw-del-tile-value">{{ $counts[$key] ?? 0 }}</span>
                    <span>{{ $label }}</span>
                </a>
            </div>
        @endforeach
        <div class="col-6 col-lg-3">
            <a href="{{ route('deliveries.index') }}" class="fw-del-tile">
                <span class="fw-del-tile-value">{{ $counts->sum() }}</span>
                <span>Total</span>
            </a>
        </div>
    </div>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div class="d-flex flex-wrap gap-2">
            @foreach ($filters as $value => $label)
                @continue($value === 'cancelled' && empty($counts['cancelled']))
                @continue($value === 'failed' && empty($counts['failed']))
                <a href="{{ route('deliveries.index', array_filter(['status' => $value, 'q' => request('q')])) }}"
                   @class(['fw-pill', 'is-active' => ($status?->value ?? '') === $value])>
                    {{ $label }}@if ($value === 'failed') <span class="ms-1">{{ $counts['failed'] }}</span>@endif
                </a>
            @endforeach
        </div>
        <div class="d-flex flex-wrap gap-2">
            <form method="GET" class="d-flex">
                @if ($status)<input type="hidden" name="status" value="{{ $status->value }}">@endif
                <input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Search deliveries" aria-label="Search by number, customer, phone or vehicle" style="min-width: 220px">
            </form>
            @can('create', App\Models\Delivery::class)
                <a href="{{ route('deliveries.create') }}" class="btn fw-btn-accent">
                    <span aria-hidden="true" class="me-1">+</span> Schedule Delivery
                </a>
            @endcan
        </div>
    </div>

    <div class="d-flex flex-column gap-3">
        @forelse ($deliveries as $delivery)
            <article class="fw-card fw-del-card">
                <div class="fw-del-icon" aria-hidden="true">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.62l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/></svg>
                </div>

                <div class="flex-grow-1 min-w-0">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <a href="{{ route('deliveries.show', $delivery) }}" class="fw-del-no">{{ $delivery->delivery_no }}</a>
                        @include('deliveries.partials.status-badge', ['status' => $delivery->status])
                        @if ($delivery->isOverdue())
                            <span class="fw-status is-critical"><span aria-hidden="true">!</span> Overdue</span>
                        @endif
                        @if ($delivery->sale)
                            <span class="small text-secondary">{{ $delivery->sale->invoice_no }}</span>
                        @endif
                    </div>
                    <div class="mt-1">{{ $delivery->customer_name }}</div>
                    <div class="fw-del-meta">
                        <span><svg aria-hidden="true" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.79 19.79 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                            <a href="tel:{{ $delivery->phone }}" class="text-reset text-decoration-none">{{ $delivery->phone }}</a></span>
                        @if ($delivery->driver_name || $delivery->vehicle_no)
                            <span><svg aria-hidden="true" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="7" width="15" height="10" rx="1"/><path d="M16 10h4l3 3v4h-7"/><circle cx="6" cy="18" r="1.5"/><circle cx="19" cy="18" r="1.5"/></svg>
                                {{ collect([$delivery->driver_name, $delivery->vehicle_no])->filter()->implode(' · ') }}</span>
                        @else
                            <span class="text-danger">No driver assigned</span>
                        @endif
                        <span class="text-truncate" style="max-width: 360px"><svg aria-hidden="true" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            {{ \Illuminate\Support\Str::limit(str_replace("\n", ', ', $delivery->address), 60) }}</span>
                    </div>
                    @if (in_array($delivery->status->value, ['failed', 'cancelled'], true) && $delivery->failure_reason)
                        <div class="small text-danger mt-1">{{ $delivery->failure_reason }}</div>
                    @endif
                </div>

                <div class="text-end flex-shrink-0">
                    <div class="fw-semibold">{{ money($delivery->totalValue()) }}</div>
                    <div class="small text-secondary mb-2">
                        {{ $delivery->status->value === 'delivered' ? 'Delivered '.$delivery->delivered_at?->format('d M Y') : $delivery->scheduled_date->format('d M Y') }}
                        @if ($delivery->time_slot && $delivery->status->value !== 'delivered')· {{ $delivery->time_slot }}@endif
                    </div>
                    @include('deliveries.partials.actions')
                </div>
            </article>
        @empty
            <div class="fw-card p-5 text-center text-secondary">
                No deliveries {{ $status ? 'with this status' : 'yet' }}.
                @can('create', App\Models\Delivery::class)
                    <a href="{{ route('deliveries.create') }}">Schedule one</a>.
                @endcan
            </div>
        @endforelse
    </div>

    <div class="mt-3">{{ $deliveries->links() }}</div>
@endsection
