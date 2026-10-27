@extends('layouts.app', ['breadcrumbs' => ['Deliveries' => route('deliveries.index'), $delivery->delivery_no => null]])

@section('title', 'Delivery '.$delivery->delivery_no)

@section('page-actions')
    @if ($delivery->status->isEditable())
        @can('update', $delivery)
            <a href="{{ route('deliveries.edit', $delivery) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
        @endcan
    @endif
@endsection

@section('content')
<div class="fw-grid gap-3" style="max-width: 1100px">
    <div class="fw-span-lg-7">
        <section class="fw-card p-3 p-md-4">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                <div>
                    <p class="fw-label mb-1">{{ $delivery->delivery_no }}</p>
                    <p class="h5 mb-1">{{ $delivery->customer_name }}</p>
                    @include('deliveries.partials.status-badge', ['status' => $delivery->status])
                    @if ($delivery->isOverdue())<span class="fw-status is-critical ms-1"><span aria-hidden="true">!</span> Overdue</span>@endif
                </div>
                <div class="text-end">
                    <p class="h5 mb-0">{{ money($delivery->totalValue()) }}</p>
                    @if ((float) $delivery->delivery_charge > 0)
                        <p class="small text-secondary mb-0">{{ money($delivery->order_value) }} + {{ money($delivery->delivery_charge) }} delivery</p>
                    @endif
                </div>
            </div>

            <dl class="fw-grid small mb-0">
                <dt class="fw-span-sm-4 text-secondary fw-normal">Phone</dt><dd class="fw-span-sm-8"><a href="tel:{{ $delivery->phone }}">{{ $delivery->phone }}</a></dd>
                <dt class="fw-span-sm-4 text-secondary fw-normal">Address</dt><dd class="fw-span-sm-8" style="white-space: pre-line">{{ $delivery->address }}</dd>
                <dt class="fw-span-sm-4 text-secondary fw-normal">Scheduled</dt><dd class="fw-span-sm-8">{{ $delivery->scheduled_date->format('l, j M Y') }}@if ($delivery->time_slot) · {{ $delivery->time_slot }}@endif</dd>
                <dt class="fw-span-sm-4 text-secondary fw-normal">Driver</dt>
                <dd class="fw-span-sm-8">
                    {{ $delivery->driver_name ?? 'Not assigned' }}
                    @if ($delivery->driver_phone) · <a href="tel:{{ $delivery->driver_phone }}">{{ $delivery->driver_phone }}</a>@endif
                    @if ($delivery->driver) <span class="text-secondary">({{ $delivery->driver->employee_code }})</span>@endif
                </dd>
                <dt class="fw-span-sm-4 text-secondary fw-normal">Vehicle</dt><dd class="fw-span-sm-8">{{ $delivery->vehicle_no ?? '—' }}</dd>
                @if ($delivery->sale)
                    <dt class="fw-span-sm-4 text-secondary fw-normal">Invoice</dt>
                    <dd class="fw-span-sm-8">
                        @can('view', $delivery->sale)
                            <a href="{{ route('sales.show', $delivery->sale_id) }}">{{ $delivery->sale->invoice_no }}</a>
                        @else
                            {{ $delivery->sale->invoice_no }}
                        @endcan
                    </dd>
                @endif
                @if ($delivery->customer)
                    <dt class="fw-span-sm-4 text-secondary fw-normal">Customer</dt><dd class="fw-span-sm-8">{{ $delivery->customer->name }} ({{ $delivery->customer->code }})</dd>
                @endif
                @if ($delivery->notes)
                    <dt class="fw-span-sm-4 text-secondary fw-normal">Instructions</dt><dd class="fw-span-sm-8" style="white-space: pre-line">{{ $delivery->notes }}</dd>
                @endif
                @if ($delivery->failure_reason)
                    <dt class="fw-span-sm-4 text-secondary fw-normal">{{ $delivery->status->value === 'cancelled' ? 'Cancel reason' : 'Last failure' }}</dt><dd class="fw-span-sm-8 text-danger">{{ $delivery->failure_reason }}</dd>
                @endif
            </dl>

            <div class="mt-3">@include('deliveries.partials.actions')</div>
        </section>
    </div>

    <div class="fw-span-lg-5">
        <section class="fw-card p-3 p-md-4">
            <h2 class="fw-card-title">Progress</h2>
            @php
                $steps = [
                    ['Scheduled', $delivery->created_at, 'by '.($delivery->creator?->name ?? '—')],
                    ['Out for delivery', $delivery->dispatched_at, $delivery->attempts > 1 ? "attempt {$delivery->attempts}" : null],
                    ['Delivered', $delivery->delivered_at, $delivery->received_by ? "received by {$delivery->received_by}" : null],
                ];
            @endphp
            <ol class="fw-timeline list-unstyled mb-0">
                @foreach ($steps as [$label, $at, $detail])
                    <li @class(['fw-timeline-item', 'is-pending' => ! $at])>
                        <span @class(['fw-semibold' => $at, 'text-secondary' => ! $at])>{{ $label }}</span>
                        @if ($at)
                            <div class="small text-secondary">{{ $at->format('j M Y, g:i A') }}@if ($detail) · {{ $detail }}@endif</div>
                        @endif
                    </li>
                @endforeach
            </ol>
        </section>
    </div>
</div>
@endsection
