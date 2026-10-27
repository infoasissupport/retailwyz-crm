@extends('layouts.app', ['breadcrumbs' => ['Customers' => route('customers.index'), $customer->name => null]])

@section('title', $customer->name)

@section('page-actions')
    @can('customers.manage')
        <a href="{{ route('customers.edit', $customer) }}" class="btn btn-sm btn-outline-primary">Edit</a>
        <form method="POST" action="{{ route('customers.destroy', $customer) }}" class="d-inline">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="Delete this customer?">Delete</button>
        </form>
    @endcan
@endsection

@section('content')
    <div class="row g-3">
        <div class="col-lg-5">
            <dl class="row mb-0">
                <dt class="col-sm-4">Code</dt><dd class="col-sm-8">{{ $customer->code }}</dd>
                <dt class="col-sm-4">Phone</dt><dd class="col-sm-8">{{ $customer->phone ?? '—' }}</dd>
                <dt class="col-sm-4">Email</dt><dd class="col-sm-8">{{ $customer->email ?? '—' }}</dd>
                <dt class="col-sm-4">Tax number</dt><dd class="col-sm-8">{{ $customer->tax_number ?? '—' }}</dd>
                <dt class="col-sm-4">Address</dt><dd class="col-sm-8">{{ $customer->address ?? '—' }}</dd>
                <dt class="col-sm-4">Opening balance</dt><dd class="col-sm-8">{{ money($customer->opening_balance) }}</dd>
                <dt class="col-sm-4">Credit limit</dt><dd class="col-sm-8">{{ $customer->credit_limit !== null ? money($customer->credit_limit) : '—' }}</dd>
                <dt class="col-sm-4">Outstanding</dt><dd class="col-sm-8">{{ money($outstanding) }}</dd>
                <dt class="col-sm-4">Active</dt><dd class="col-sm-8">{{ $customer->is_active ? 'Yes' : 'No' }}</dd>
            </dl>
        </div>

        <div class="col-lg-7">
            <h2 class="h6">Recent sales</h2>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead><tr><th>Invoice</th><th>Date</th><th>Branch</th><th>Status</th><th class="text-end">Total</th><th class="text-end">Due</th></tr></thead>
                    <tbody>
                        @forelse ($sales as $sale)
                            <tr>
                                <td><a href="{{ route('sales.show', $sale) }}">{{ $sale->invoice_no }}</a></td>
                                <td>{{ $sale->sale_date->format('d-m-Y') }}</td>
                                <td>{{ $sale->branch->name }}</td>
                                <td>{{ $sale->status->label() }} / {{ $sale->payment_status->label() }}</td>
                                <td class="text-end">{{ money($sale->grand_total) }}</td>
                                <td class="text-end">{{ money($sale->balanceDue()) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-muted">No sales yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
