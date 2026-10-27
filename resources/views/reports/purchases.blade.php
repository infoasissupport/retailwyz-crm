@extends('layouts.app', ['breadcrumbs' => ['Reports' => route('reports.index'), 'Purchases' => null]])

@section('title', 'Purchase report')

@section('content')
    @include('reports.partials.period-filter')

    <dl class="fw-grid mb-4 fw-maxw-520">
        <dt class="fw-span-7">Purchase orders</dt><dd class="fw-span-5 text-end">{{ $totals['orders'] }}</dd>
        <dt class="fw-span-7">Gross (before discount)</dt><dd class="fw-span-5 text-end">{{ money($totals['gross']) }}</dd>
        <dt class="fw-span-7">Discounts</dt><dd class="fw-span-5 text-end">{{ money($totals['discounts']) }}</dd>
        <dt class="fw-span-7">Tax</dt><dd class="fw-span-5 text-end">{{ money($totals['tax']) }}</dd>
        <dt class="fw-span-7">Shipping</dt><dd class="fw-span-5 text-end">{{ money($totals['shipping']) }}</dd>
        <dt class="fw-span-7">Total</dt><dd class="fw-span-5 text-end">{{ money($totals['total']) }}</dd>
        <dt class="fw-span-7">Paid</dt><dd class="fw-span-5 text-end">{{ money($totals['paid']) }}</dd>
        <dt class="fw-span-7">Returns ({{ $totals['returns_count'] }})</dt><dd class="fw-span-5 text-end">{{ money($totals['returns']) }}</dd>
        <dt class="fw-span-7">Net purchases</dt><dd class="fw-span-5 text-end fw-semibold">{{ money($totals['net']) }}</dd>
    </dl>

    <h2 class="h6">By supplier</h2>
    <table class="table table-sm fw-maxw-720">
        <thead><tr><th>Supplier</th><th class="text-end">Orders</th><th class="text-end">Total</th><th class="text-end">Paid</th></tr></thead>
        <tbody>
            @forelse ($bySupplier as $row)
                <tr>
                    <td>{{ $row->supplier }}</td>
                    <td class="text-end">{{ $row->orders }}</td>
                    <td class="text-end">{{ money($row->total) }}</td>
                    <td class="text-end">{{ money($row->paid) }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-muted">No purchases in this period.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
