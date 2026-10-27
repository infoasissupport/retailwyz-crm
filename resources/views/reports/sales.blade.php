@extends('layouts.app', ['breadcrumbs' => ['Reports' => route('reports.index'), 'Sales' => null]])

@section('title', 'Sales report')

@section('content')
    @include('reports.partials.period-filter')

    <dl class="row mb-4" style="max-width: 520px">
        <dt class="col-7">Invoices</dt><dd class="col-5 text-end">{{ $totals['invoices'] }}</dd>
        <dt class="col-7">Gross (before discount)</dt><dd class="col-5 text-end">{{ money($totals['gross']) }}</dd>
        <dt class="col-7">Discounts</dt><dd class="col-5 text-end">{{ money($totals['discounts']) }}</dd>
        <dt class="col-7">Tax</dt><dd class="col-5 text-end">{{ money($totals['tax']) }}</dd>
        <dt class="col-7">Invoiced total</dt><dd class="col-5 text-end">{{ money($totals['total']) }}</dd>
        <dt class="col-7">Returns ({{ $totals['returns_count'] }})</dt><dd class="col-5 text-end">{{ money($totals['returns']) }}</dd>
        <dt class="col-7">Net sales</dt><dd class="col-5 text-end fw-semibold">{{ money($totals['net']) }}</dd>
    </dl>

    <div class="row g-4">
        <div class="col-lg-6">
            <h2 class="h6">By day</h2>
            <table class="table table-sm">
                <thead><tr><th>Date</th><th class="text-end">Invoices</th><th class="text-end">Tax</th><th class="text-end">Total</th></tr></thead>
                <tbody>
                    @forelse ($byDay as $row)
                        <tr>
                            <td>{{ \Illuminate\Support\Carbon::parse($row->day)->format('d-m-Y') }}</td>
                            <td class="text-end">{{ $row->invoices }}</td>
                            <td class="text-end">{{ money($row->tax) }}</td>
                            <td class="text-end">{{ money($row->total) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-muted">No sales in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="col-lg-6">
            <h2 class="h6">By category</h2>
            <table class="table table-sm">
                <thead><tr><th>Category</th><th class="text-end">Qty</th><th class="text-end">Net of tax</th><th class="text-end">Total</th></tr></thead>
                <tbody>
                    @forelse ($byCategory as $row)
                        <tr>
                            <td>{{ $row->category }}</td>
                            <td class="text-end">{{ qty($row->quantity) }}</td>
                            <td class="text-end">{{ money($row->net) }}</td>
                            <td class="text-end">{{ money($row->total) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-muted">No sales in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
