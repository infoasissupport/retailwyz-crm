@extends('layouts.app', ['breadcrumbs' => ['Payments' => route('payments.index'), 'Receivables' => null]])

@section('title', 'Customer receivables')

@section('content')
    <p>Total outstanding: <strong>{{ money($total) }}</strong></p>

    <div class="row g-3">
        <div class="col-lg-4">
            <h2 class="h6">By customer</h2>
            <table class="table table-sm">
                <thead><tr><th>Customer</th><th class="text-end">Invoices</th><th class="text-end">Balance</th></tr></thead>
                <tbody>
                    @forelse ($byCustomer as $row)
                        <tr><td>{{ $row->name }}</td><td class="text-end">{{ $row->documents }}</td><td class="text-end">{{ money($row->balance) }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="text-muted">Nothing outstanding.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="col-lg-8">
            <h2 class="h6">Open invoices</h2>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead><tr><th>Invoice</th><th>Date</th><th>Customer</th><th class="text-end">Total</th><th class="text-end">Paid</th><th class="text-end">Balance</th></tr></thead>
                    <tbody>
                        @forelse ($sales as $sale)
                            <tr>
                                <td><a href="{{ route('sales.show', $sale) }}">{{ $sale->invoice_no }}</a></td>
                                <td>{{ $sale->sale_date->format('d-m-Y') }}</td>
                                <td>{{ $sale->customer?->name ?? 'Walk-in' }}</td>
                                <td class="text-end">{{ money($sale->grand_total) }}</td>
                                <td class="text-end">{{ money($sale->paid_amount) }}</td>
                                <td class="text-end">{{ money($sale->balance) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-muted">No open invoices.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $sales->links() }}
        </div>
    </div>
@endsection
