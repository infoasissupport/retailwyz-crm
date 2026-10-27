@extends('layouts.app', ['breadcrumbs' => ['Payments' => route('payments.index'), 'Payables' => null]])

@section('title', 'Supplier payables')

@section('content')
    <p>Total outstanding: <strong>{{ money($total) }}</strong></p>

    <div class="row g-3">
        <div class="col-lg-4">
            <h2 class="h6">By supplier</h2>
            <table class="table table-sm">
                <thead><tr><th>Supplier</th><th class="text-end">Bills</th><th class="text-end">Balance</th></tr></thead>
                <tbody>
                    @forelse ($bySupplier as $row)
                        <tr><td>{{ $row->name }}</td><td class="text-end">{{ $row->documents }}</td><td class="text-end">{{ money($row->balance) }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="text-muted">Nothing outstanding.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="col-lg-8">
            <h2 class="h6">Open purchases</h2>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead><tr><th>Reference</th><th>Date</th><th>Supplier</th><th class="text-end">Total</th><th class="text-end">Paid</th><th class="text-end">Balance</th></tr></thead>
                    <tbody>
                        @forelse ($purchases as $purchase)
                            <tr>
                                <td><a href="{{ route('purchases.show', $purchase) }}">{{ $purchase->reference_no }}</a></td>
                                <td>{{ $purchase->purchase_date->format('d-m-Y') }}</td>
                                <td>{{ $purchase->supplier->name }}</td>
                                <td class="text-end">{{ money($purchase->grand_total) }}</td>
                                <td class="text-end">{{ money($purchase->paid_amount) }}</td>
                                <td class="text-end">{{ money($purchase->balance) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-muted">No open purchases.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $purchases->links() }}
        </div>
    </div>
@endsection
