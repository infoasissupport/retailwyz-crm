@extends('layouts.app', ['breadcrumbs' => ['Reports' => route('reports.index'), 'Purchases' => null]])

@section('title', 'Purchase report')

@section('content')
    @include('reports.partials.period-filter')

    <dl class="row mb-4" style="max-width: 520px">
        <dt class="col-7">Purchase orders</dt><dd class="col-5 text-end">{{ $totals['orders'] }}</dd>
        <dt class="col-7">Gross (before discount)</dt><dd class="col-5 text-end">{{ money($totals['gross']) }}</dd>
        <dt class="col-7">Discounts</dt><dd class="col-5 text-end">{{ money($totals['discounts']) }}</dd>
        <dt class="col-7">Tax</dt><dd class="col-5 text-end">{{ money($totals['tax']) }}</dd>
        <dt class="col-7">Shipping</dt><dd class="col-5 text-end">{{ money($totals['shipping']) }}</dd>
        <dt class="col-7">Total</dt><dd class="col-5 text-end">{{ money($totals['total']) }}</dd>
        <dt class="col-7">Paid</dt><dd class="col-5 text-end">{{ money($totals['paid']) }}</dd>
        <dt class="col-7">Returns ({{ $totals['returns_count'] }})</dt><dd class="col-5 text-end">{{ money($totals['returns']) }}</dd>
        <dt class="col-7">Net purchases</dt><dd class="col-5 text-end fw-semibold">{{ money($totals['net']) }}</dd>
    </dl>

    <h2 class="h6">By supplier</h2>
    <table class="table table-sm" style="max-width: 720px">
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
