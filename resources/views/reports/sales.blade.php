@extends('layouts.app', ['breadcrumbs' => ['Reports' => route('reports.index'), 'Sales' => null]])

@section('title', 'Sales report')

@section('content')
    @include('reports.partials.period-filter')

    <dl class="fw-grid mb-4 fw-maxw-520">
        <dt class="fw-span-7">Invoices</dt><dd class="fw-span-5 text-end">{{ $totals['invoices'] }}</dd>
        <dt class="fw-span-7">Gross (before discount)</dt><dd class="fw-span-5 text-end">{{ money($totals['gross']) }}</dd>
        <dt class="fw-span-7">Discounts</dt><dd class="fw-span-5 text-end">{{ money($totals['discounts']) }}</dd>
        <dt class="fw-span-7">Tax</dt><dd class="fw-span-5 text-end">{{ money($totals['tax']) }}</dd>
        <dt class="fw-span-7">Invoiced total</dt><dd class="fw-span-5 text-end">{{ money($totals['total']) }}</dd>
        <dt class="fw-span-7">Returns ({{ $totals['returns_count'] }})</dt><dd class="fw-span-5 text-end">{{ money($totals['returns']) }}</dd>
        <dt class="fw-span-7">Net sales</dt><dd class="fw-span-5 text-end fw-semibold">{{ money($totals['net']) }}</dd>
    </dl>

    <div class="fw-grid gap-4">
        <div class="fw-span-lg-6">
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
        <div class="fw-span-lg-6">
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
