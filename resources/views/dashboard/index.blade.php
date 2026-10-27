@extends('layouts.app')

@section('title', 'Dashboard')
@section('subtitle')
    Welcome back, {{ \Illuminate\Support\Str::before(auth()->user()->name.' ', ' ') }}! Here's what's happening with your business
    {{ $preset === 'today' ? 'today' : ($preset ? strtolower($presets[$preset]) : 'in the selected period') }}.
@endsection

@section('header-tools')
    <div class="dropdown">
        <button type="button" class="fw-range-btn" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
            <i class="bi bi-calendar3" aria-hidden="true"></i>
            <span class="d-none d-sm-inline">{{ $from->format('d M Y') }}@unless ($from->equalTo($to)) – {{ $to->format('d M Y') }}@endunless</span>
            <i class="bi bi-chevron-down small" aria-hidden="true"></i>
        </button>
        <div class="dropdown-menu dropdown-menu-end fw-dropdown" style="width: 280px">
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
<div class="fw-dash">
    @if (empty($kpis) && ! isset($recentLeads, $todaysDeliveries, $activeOrders, $lowStock))
        <div class="fw-card p-4 text-secondary">Your account has no dashboard widgets yet. Use the menu to get started.</div>
    @endif

    {{-- KPI tiles --}}
    @if ($kpis)
        <div class="row g-3 mb-3">
            @foreach ($kpis as $kpi)
                <div class="col-sm-6 col-xl-3">
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

    <div class="row g-3">
        {{-- Recent leads --}}
        @isset($recentLeads)
            <div class="{{ isset($todaysDeliveries) ? 'col-xl-7' : 'col-12' }}">
                <section class="fw-card fw-panel h-100" aria-labelledby="leads-title">
                    <div class="fw-panel-head">
                        <h2 id="leads-title" class="fw-panel-title"><i class="bi bi-person-lines-fill text-primary-emphasis" aria-hidden="true"></i> Recent Leads</h2>
                        <a href="{{ route('leads.index') }}" class="fw-view-all">View All <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                    </div>
                    <div class="table-responsive">
                        <table class="table fw-dtable mb-0">
                            <thead><tr><th>#</th><th>Name</th><th>Contact</th><th>Source</th><th>Date</th><th>Status</th><th class="text-end">Action</th></tr></thead>
                            <tbody>
                                @forelse ($recentLeads as $lead)
                                    <tr>
                                        <td class="text-secondary">{{ $loop->iteration }}</td>
                                        <td>
                                            <span class="d-flex align-items-center gap-2">
                                                <span class="fw-initials">{{ \Illuminate\Support\Str::of($lead->name)->explode(' ')->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('') }}</span>
                                                <span class="fw-semibold text-nowrap">{{ $lead->name }}</span>
                                            </span>
                                        </td>
                                        <td class="text-nowrap">{{ $lead->phone ?? $lead->email }}</td>
                                        <td class="text-nowrap">
                                            <i class="bi {{ ['walk_in' => 'bi-person-walking', 'phone' => 'bi-telephone', 'whatsapp' => 'bi-whatsapp', 'website' => 'bi-globe', 'social_media' => 'bi-instagram', 'referral' => 'bi-people'][$lead->source->value] ?? 'bi-dot' }} text-secondary me-1" aria-hidden="true"></i>{{ $lead->source->label() }}
                                        </td>
                                        <td class="text-nowrap">{{ $lead->created_at->format('d M Y') }}</td>
                                        <td><span class="fw-tag tag-{{ $lead->status->value }}">{{ $lead->status->label() }}</span></td>
                                        <td class="text-end">
                                            <a href="{{ route('leads.show', $lead) }}" class="fw-row-btn" aria-label="View {{ $lead->name }}"><i class="bi bi-eye" aria-hidden="true"></i></a>
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
            <div class="{{ isset($recentLeads) ? 'col-xl-5' : 'col-12' }}">
                <section class="fw-card fw-panel h-100" aria-labelledby="del-title">
                    <div class="fw-panel-head">
                        <h2 id="del-title" class="fw-panel-title"><i class="bi bi-truck text-info-emphasis" aria-hidden="true"></i> Today's Deliveries</h2>
                        <a href="{{ route('deliveries.index') }}" class="fw-view-all">View All <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                    </div>
                    <ul class="list-unstyled mb-0 fw-del-list">
                        @forelse ($todaysDeliveries as $delivery)
                            <li>
                                <a href="{{ route('deliveries.show', $delivery) }}" class="fw-del-row">
                                    <span class="fw-del-thumb" aria-hidden="true"><i class="bi bi-box-seam"></i></span>
                                    <span class="min-w-0 flex-grow-1">
                                        <span class="d-block fw-semibold">{{ $delivery->delivery_no }}</span>
                                        <span class="d-block small text-truncate">{{ $delivery->customer_name }}@if ($delivery->sale) · {{ $delivery->sale->invoice_no }}@endif</span>
                                        <span class="d-block small text-secondary text-truncate">{{ \Illuminate\Support\Str::of($delivery->address)->replace("\n", ', ')->limit(40) }}</span>
                                    </span>
                                    <span class="text-end flex-shrink-0">
                                        @include('deliveries.partials.status-badge', ['status' => $delivery->status])
                                        <span class="d-block small text-secondary mt-1">
                                            {{ $delivery->delivered_at?->format('g:i A') ?? $delivery->dispatched_at?->format('g:i A') ?? $delivery->time_slot ?? '—' }}
                                        </span>
                                    </span>
                                </a>
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
            <div class="{{ isset($lowStock) ? 'col-xl-6' : 'col-12' }}">
                <section class="fw-card fw-panel h-100" aria-labelledby="orders-title">
                    <div class="fw-panel-head">
                        <h2 id="orders-title" class="fw-panel-title"><i class="bi bi-cart3 text-danger-emphasis" aria-hidden="true"></i> Active Orders</h2>
                        <a href="{{ route('payments.receivables') }}" class="fw-view-all">View All <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                    </div>
                    <div class="table-responsive">
                        <table class="table fw-dtable mb-0">
                            <thead><tr><th>Order No</th><th>Customer / Items</th><th class="text-end">Due</th><th>Status</th><th class="text-end">Action</th></tr></thead>
                            <tbody>
                                @forelse ($activeOrders as $order)
                                    <tr>
                                        <td class="fw-semibold text-nowrap">{{ $order->invoice_no }}</td>
                                        <td style="max-width: 220px">
                                            <span class="d-block text-truncate">{{ $order->customer?->name ?? 'Walk-in' }}</span>
                                            <span class="d-block small text-secondary text-truncate">{{ $order->items->first()?->description }}@if ($order->items->count() > 1) +{{ $order->items->count() - 1 }} more @endif</span>
                                        </td>
                                        <td class="text-end text-nowrap fw-num">{{ money($order->balanceDue()) }}</td>
                                        <td>
                                            <span class="fw-tag {{ $order->payment_status->value === 'partial' ? 'tag-partial' : 'tag-unpaid' }}">
                                                {{ $order->payment_status->value === 'partial' ? 'Partially Paid' : 'Unpaid' }}
                                            </span>
                                        </td>
                                        <td class="text-end"><a href="{{ route('sales.show', $order) }}" class="fw-row-btn" aria-label="View {{ $order->invoice_no }}"><i class="bi bi-eye" aria-hidden="true"></i></a></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-secondary py-4">No orders awaiting payment.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        @endisset

        {{-- Low stock alerts --}}
        @isset($lowStock)
            <div class="{{ isset($activeOrders) ? 'col-xl-6' : 'col-12' }}">
                <section class="fw-card fw-panel h-100" aria-labelledby="stock-title">
                    <div class="fw-panel-head">
                        <h2 id="stock-title" class="fw-panel-title"><i class="bi bi-exclamation-triangle-fill text-warning" aria-hidden="true"></i> Low Stock Alerts</h2>
                        <a href="{{ route('inventory.index', ['low_stock' => 1]) }}" class="fw-view-all">View All <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                    </div>
                    <div class="table-responsive">
                        <table class="table fw-dtable mb-0">
                            <thead><tr><th>#</th><th>Item</th><th class="text-end">Current</th><th class="text-end">Min.</th><th>Status</th><th class="text-end">Action</th></tr></thead>
                            <tbody>
                                @forelse ($lowStock as $item)
                                    @php $out = (float) $item->quantity <= 0; @endphp
                                    <tr>
                                        <td class="text-secondary">{{ $loop->iteration }}</td>
                                        <td style="max-width: 220px">
                                            <span class="d-block text-truncate">{{ $item->name }}</span>
                                            <span class="d-block small text-secondary">{{ $item->sku }}</span>
                                        </td>
                                        <td class="text-end fw-num">{{ qty($item->quantity) }}</td>
                                        <td class="text-end fw-num">{{ qty($item->min_stock) }}</td>
                                        <td><span class="fw-tag {{ $out ? 'tag-out' : 'tag-low' }}">{{ $out ? 'Out of Stock' : 'Low Stock' }}</span></td>
                                        <td class="text-end"><a href="{{ route('products.show', $item->id) }}" class="fw-row-btn" aria-label="View {{ $item->name }}"><i class="bi bi-eye" aria-hidden="true"></i></a></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-secondary py-4"><i class="bi bi-check2-circle me-1" aria-hidden="true"></i>All products are above their minimum stock.</td></tr>
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
