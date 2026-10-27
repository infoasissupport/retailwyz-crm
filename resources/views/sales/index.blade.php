@extends('layouts.app', ['breadcrumbs' => ['Sales' => null]])

@section('title', 'Sales')

@section('page-actions')
    @can('sales.create')
        <a href="{{ route('sales.create') }}" class="btn btn-sm btn-primary">New sale</a>
    @endcan
@endsection

@section('content')
    <form method="GET" class="fw-grid gap-2">
        <div class="fw-span-md-2"><input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Invoice no."></div>
        <div class="fw-span-md-2">
            <select name="customer_id" class="form-select">
                <option value="">All customers</option>
                @foreach ($customers as $id => $name)
                    <option value="{{ $id }}" @selected(request('customer_id') == $id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div class="fw-span-md-2">
            <select name="status" class="form-select">
                <option value="">All statuses</option>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="fw-span-md-2">
            <select name="payment_status" class="form-select">
                <option value="">Any payment</option>
                @foreach ($paymentStatuses as $value => $label)
                    <option value="{{ $value }}" @selected(request('payment_status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="fw-span-md-1"><input type="date" name="from" value="{{ request('from') }}" class="form-control" aria-label="From"></div>
        <div class="fw-span-md-1"><input type="date" name="to" value="{{ request('to') }}" class="form-control" aria-label="To"></div>
        <div class="fw-span-md-2"><button type="submit" class="btn btn-outline-secondary w-100">Filter</button></div>
    </form>

    <div class="table-responsive">
        <table class="table table-sm align-middle">
            <thead>
                <tr>
                    <th>Invoice</th><th>Date</th><th>Customer</th><th>Branch</th><th>Status</th>
                    <th class="text-end">Total</th><th class="text-end">Balance</th><th>Payment</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($sales as $sale)
                    <tr>
                        <td><a href="{{ route('sales.show', $sale) }}">{{ $sale->invoice_no }}</a></td>
                        <td>{{ $sale->sale_date->format('d-m-Y') }}</td>
                        <td>{{ $sale->customer?->name ?? 'Walk-in' }}</td>
                        <td>{{ $sale->branch->name }}</td>
                        <td>{{ $sale->status->label() }}</td>
                        <td class="text-end">{{ money($sale->grand_total) }}</td>
                        <td class="text-end">{{ money($sale->balanceDue()) }}</td>
                        <td>{{ $sale->payment_status->label() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted">No sales found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $sales->links() }}
@endsection
