@extends('layouts.app')

@section('title', 'Dashboard')

@section('header-tools')
    <div class="dropdown">
        <button type="button" class="fw-range-btn" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
            <i class="bi bi-calendar3" aria-hidden="true"></i>
            <span class="d-none d-sm-inline">{{ $from->format('d M Y') }}@unless ($from->equalTo($to)) – {{ $to->format('d M Y') }}@endunless</span>
            <i class="bi bi-chevron-down small" aria-hidden="true"></i>
        </button>
        <div class="dropdown-menu dropdown-menu-end fw-dropdown fw-w-280">
            <div class="fw-dropdown-head">Show figures for</div>
            @foreach ($presets as $key => $label)
                <a href="{{ route('dashboard', ['range' => $key]) }}" @class(['dropdown-item d-flex justify-content-between', 'active' => $preset === $key])>
                    {{ $label }} @if ($preset === $key)<i class="bi bi-check2" aria-hidden="true"></i>@endif
                </a>
            @endforeach
            <form method="GET" action="{{ route('dashboard') }}" class="px-2 pt-2 mt-1 border-top">
                <div class="small fw-semibold text-secondary mb-1">Custom range</div>
                <div class="d-flex gap-2 mb-2">
                    <input type="date" name="from" value="{{ $from->toDateString() }}" max="{{ today()->toDateString() }}" class="form-control form-control-sm" aria-label="From">
                    <input type="date" name="to" value="{{ $to->toDateString() }}" max="{{ today()->toDateString() }}" class="form-control form-control-sm" aria-label="To">
                </div>
                <button type="submit" class="btn btn-sm btn-primary w-100">Apply</button>
            </form>
        </div>
    </div>
@endsection

@section('content')
@php
    $initials = fn (?string $name) => \Illuminate\Support\Str::of((string) $name)->explode(' ')->filter()->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('') ?: '?';
    $avatarTones = ['blue', 'violet', 'rose', 'amber', 'green', 'sky'];
    $avatarTone = fn (?string $name) => $avatarTones[crc32((string) $name) % count($avatarTones)];
    $sourceIcons = [
        'walk_in' => 'bi-person-walking',
        'phone' => 'bi-telephone',
        'whatsapp' => 'bi-whatsapp',
        'website' => 'bi-globe2',
        'social_media' => 'bi-instagram',
        'referral' => 'bi-people',
        'other' => 'bi-three-dots',
    ];
@endphp
<div class="fw-dash">


    @if (empty($kpis) && ! isset($recentLeads, $todaysDeliveries, $activeOrders))
        <div class="fw-card p-4 text-secondary">Your account has no dashboard widgets yet. Use the menu to get started.</div>
    @endif

    {{-- KPI tiles --}}
    @if ($kpis)
        <div class="fw-grid">
            @foreach ($kpis as $kpi)
                <div class="fw-span-sm-6 fw-span-xl-3">
                    <a href="{{ $kpi['url'] }}" class="fw-kpi tone-{{ $kpi['tone'] }}">
                        <span class="fw-kpi-icon" aria-hidden="true"><i class="bi {{ $kpi['icon'] }}"></i></span>
                        <span class="fw-kpi-body">
                            <span class="fw-kpi-label">{{ $kpi['label'] }}</span>
                            <span class="fw-kpi-value-row">
                                <span class="fw-kpi-value">{{ $kpi['display'] }}</span>
                                @if ($kpi['delta'] !== null)
                                    @php $dir = $kpi['delta'] > 0 ? 'up' : ($kpi['delta'] < 0 ? 'down' : 'flat'); @endphp
                                    <span class="fw-delta-chip is-{{ $dir }}">
                                        <i class="bi {{ ['up' => 'bi-arrow-up', 'down' => 'bi-arrow-down', 'flat' => 'bi-arrow-right'][$dir] }}" aria-hidden="true"></i>
                                        <span class="visually-hidden">{{ ['up' => 'Up', 'down' => 'Down', 'flat' => 'No change,'][$dir] }}</span>
                                        {{ rtrim(rtrim(number_format(abs($kpi['delta']), 1), '0'), '.') }}%
                                    </span>
                                @endif
                            </span>
                            <span class="fw-kpi-caption">{{ $kpi['caption'] }}</span>
                        </span>
                        @if ($kpi['spark'] && array_sum($kpi['spark']) != 0)
                            <x-sparkline :values="$kpi['spark']" class="fw-kpi-spark" />
                        @endif
                    </a>
                </div>
            @endforeach
        </div>
    @endif

    <div class="fw-grid">
        {{-- Recent leads --}}
        @isset($recentLeads)
            <div class="{{ isset($todaysDeliveries) ? 'fw-span-xl-7' : 'fw-span-12' }}">
                <section class="fw-card fw-panel h-100" aria-labelledby="leads-title">
                    <div class="fw-panel-head">
                        <h2 id="leads-title" class="fw-panel-title"><i class="bi bi-person-lines-fill fw-panel-icon is-blue" aria-hidden="true"></i> Recent Leads</h2>
                        <a href="{{ route('leads.index') }}" class="fw-view-all">View All <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                    </div>
                    <div class="table-responsive">
                        <table class="table fw-dtable mb-0">
                            <thead><tr><th>#</th><th>Name</th><th>Contact</th><th>Source</th><th>Date</th><th>Status</th><th class="text-center">Action</th></tr></thead>
                            <tbody>
                                @forelse ($recentLeads as $lead)
                                    @php $srcIcon = $sourceIcons[$lead->source->value] ?? 'bi-dot'; @endphp
                                    <tr>
                                        <td class="text-secondary">{{ $loop->iteration }}</td>
                                        <td>
                                            <span class="d-flex align-items-center gap-2">
                                                <span class="fw-initials tone-{{ $avatarTone($lead->name) }}" aria-hidden="true">{{ $initials($lead->name) }}</span>
                                                <span class="fw-semibold text-nowrap">{{ $lead->name }}</span>
                                            </span>
                                        </td>
                                        <td class="text-nowrap">{{ $lead->phone ?? $lead->email ?? '—' }}</td>
                                        <td class="text-nowrap">
                                            <i class="bi {{ $srcIcon }} me-2 fw-src-{{ $lead->source->value }}" aria-hidden="true"></i>{{ $lead->source->label() }}
                                        </td>
                                        <td class="text-nowrap">{{ $lead->created_at->format('d M Y') }}</td>
                                        <td><span class="fw-tag tag-{{ $lead->status->value }}">{{ $lead->status->label() }}</span></td>
                                        <td>
                                            <div class="fw-row-actions">
                                                <a href="{{ route('leads.show', $lead) }}" class="fw-row-btn" aria-label="View {{ $lead->name }}"><i class="bi bi-eye" aria-hidden="true"></i></a>
                                                @can('update', $lead)
                                                    <div class="dropdown">
                                                        <button type="button" class="fw-row-btn is-plain" data-bs-toggle="dropdown" aria-expanded="false" aria-label="More actions for {{ $lead->name }}"><i class="bi bi-three-dots" aria-hidden="true"></i></button>
                                                        <ul class="dropdown-menu dropdown-menu-end">
                                                            <li><a class="dropdown-item" href="{{ route('leads.edit', $lead) }}"><i class="bi bi-pencil me-2" aria-hidden="true"></i>Edit lead</a></li>
                                                        </ul>
                                                    </div>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-center text-secondary py-4">No leads yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        @endisset

        {{-- Today's deliveries --}}
        @isset($todaysDeliveries)
            <div class="{{ isset($recentLeads) ? 'fw-span-xl-5' : 'fw-span-12' }}">
                <section class="fw-card fw-panel h-100" aria-labelledby="del-title">
                    <div class="fw-panel-head">
                        <h2 id="del-title" class="fw-panel-title"><i class="bi bi-truck fw-panel-icon is-teal" aria-hidden="true"></i> Today's Deliveries</h2>
                        <a href="{{ route('deliveries.index') }}" class="fw-view-all">View All <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                    </div>
                    <ul class="list-unstyled mb-0 fw-del-list">
                        @forelse ($todaysDeliveries as $delivery)
                            <li class="fw-del-item">
                                <a href="{{ route('deliveries.show', $delivery) }}" class="fw-del-row">
                                    {{-- Sample artwork until products carry their own photos; stable per delivery. --}}
                                    @php $samples = ['sofa', 'dining-table', 'wardrobe', 'tv-unit', 'armchair', 'bed']; @endphp
                                    <img src="{{ asset('images/samples/'.$samples[$delivery->id % count($samples)].'.svg') }}" class="fw-del-thumb" alt="" width="52" height="46" loading="lazy">
                                    <span class="min-w-0 flex-grow-1">
                                        <span class="d-block fw-semibold">{{ $delivery->delivery_no }}</span>
                                        <span class="d-block small text-truncate">{{ $delivery->customer_name }}@if ($delivery->sale) · {{ $delivery->sale->invoice_no }}@endif</span>
                                        <span class="d-block small text-secondary text-truncate">{{ \Illuminate\Support\Str::of($delivery->address)->replace("\n", ', ')->limit(40) }}</span>
                                    </span>
                                    <span class="text-end flex-shrink-0">
                                        @include('deliveries.partials.status-badge', ['status' => $delivery->status])
                                        <span class="d-block small text-secondary mt-1">
                                            {{ $delivery->delivered_at?->format('h:i A') ?? $delivery->dispatched_at?->format('h:i A') ?? $delivery->time_slot ?? '—' }}
                                        </span>
                                    </span>
                                </a>
                                <div class="dropdown">
                                    <button type="button" class="fw-row-btn is-plain" data-bs-toggle="dropdown" aria-expanded="false" aria-label="More actions for {{ $delivery->delivery_no }}"><i class="bi bi-three-dots-vertical" aria-hidden="true"></i></button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item" href="{{ route('deliveries.show', $delivery) }}"><i class="bi bi-eye me-2" aria-hidden="true"></i>View delivery</a></li>
                                        @can('update', $delivery)
                                            <li><a class="dropdown-item" href="{{ route('deliveries.edit', $delivery) }}"><i class="bi bi-pencil me-2" aria-hidden="true"></i>Edit delivery</a></li>
                                        @endcan
                                        @if ($delivery->sale)
                                            <li><a class="dropdown-item" href="{{ route('sales.show', $delivery->sale) }}"><i class="bi bi-receipt me-2" aria-hidden="true"></i>View order</a></li>
                                        @endif
                                    </ul>
                                </div>
                            </li>
                        @empty
                            <li class="text-center text-secondary py-4 small">No deliveries scheduled for today.</li>
                        @endforelse
                    </ul>
                </section>
            </div>
        @endisset

        {{-- Active orders --}}
        @isset($activeOrders)
            <div class="fw-span-12">
                <section class="fw-card fw-panel h-100" aria-labelledby="orders-title">
                    <div class="fw-panel-head">
                        <h2 id="orders-title" class="fw-panel-title"><i class="bi bi-cart-fill fw-panel-icon is-red" aria-hidden="true"></i> Active Orders</h2>
                        <a href="{{ route('payments.receivables') }}" class="fw-view-all">View All <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                    </div>
                    <div class="table-responsive">
                        <table class="table fw-dtable mb-0">
                            <thead><tr><th>Order No</th><th>Customer</th><th>Items</th><th >Amount</th><th>Status</th><th class="text-center">Action</th></tr></thead>
                            <tbody>
                                @forelse ($activeOrders as $order)
                                    <tr>
                                        <td class="fw-semibold text-nowrap">{{ $order->invoice_no }}</td>
                                        <td class="fw-cell-truncate">{{ $order->customer?->name ?? 'Walk-in' }}</td>
                                        <td class="fw-cell-truncate">{{ $order->items->first()?->description ?? '—' }}@if ($order->items->count() > 1) <span class="text-secondary">+{{ $order->items->count() - 1 }}</span>@endif</td>
                                        <td class=" text-nowrap fw-num">{{ money($order->balanceDue()) }}</td>
                                        <td>
                                            <span class="fw-tag {{ $order->payment_status->value === 'partial' ? 'tag-partial' : 'tag-unpaid' }}">
                                                {{ $order->payment_status->value === 'partial' ? 'Partially Paid' : 'Unpaid' }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="fw-row-actions">
                                                <a href="{{ route('sales.show', $order) }}" class="fw-row-btn" aria-label="View {{ $order->invoice_no }}"><i class="bi bi-eye" aria-hidden="true"></i></a>
                                                {{-- Sales have no edit screen; changes (payments, returns, cancel) are made on the order page. --}}
                                                <a href="{{ route('sales.show', $order) }}" class="fw-row-btn is-edit" title="Edit order" aria-label="Edit {{ $order->invoice_no }}"><i class="bi bi-pencil" aria-hidden="true"></i></a>
                                                <div class="dropdown">
                                                    <button type="button" class="fw-row-btn is-plain" data-bs-toggle="dropdown" aria-expanded="false" aria-label="More actions for {{ $order->invoice_no }}"><i class="bi bi-three-dots" aria-hidden="true"></i></button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li><a class="dropdown-item" href="{{ route('sales.invoice', $order) }}"><i class="bi bi-printer me-2" aria-hidden="true"></i>Invoice</a></li>
                                                        @can('payments.create')
                                                            <li><a class="dropdown-item" href="{{ route('sales.show', $order) }}"><i class="bi bi-cash-coin me-2" aria-hidden="true"></i>Record payment</a></li>
                                                        @endcan
                                                    </ul>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-secondary py-4">No orders awaiting payment.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        @endisset

    </div>
</div>
@endsection
