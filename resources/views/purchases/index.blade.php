@extends('layouts.app', ['breadcrumbs' => ['Purchases' => null]])

@section('title', 'Purchases')

@section('page-actions')
    @can('purchases.create')
        <a href="{{ route('purchases.create') }}" class="btn btn-sm btn-primary">New purchase</a>
    @endcan
@endsection

@section('content')
    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-3"><input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Reference or supplier invoice"></div>
        <div class="col-md-2">
            <select name="supplier_id" class="form-select">
                <option value="">All suppliers</option>
                @foreach ($suppliers as $id => $name)
                    <option value="{{ $id }}" @selected(request('supplier_id') == $id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <select name="status" class="form-select">
                <option value="">All statuses</option>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2"><input type="date" name="from" value="{{ request('from') }}" class="form-control" aria-label="From"></div>
        <div class="col-md-2"><input type="date" name="to" value="{{ request('to') }}" class="form-control" aria-label="To"></div>
        <div class="col-md-1"><button type="submit" class="btn btn-outline-secondary w-100">Filter</button></div>
    </form>

    <div class="table-responsive">
        <table class="table table-sm align-middle">
            <thead>
                <tr>
                    <th>Reference</th><th>Date</th><th>Supplier</th><th>Branch</th><th>Status</th>
                    <th class="text-end">Total</th><th class="text-end">Balance</th><th>Payment</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($purchases as $purchase)
                    <tr>
                        <td><a href="{{ route('purchases.show', $purchase) }}">{{ $purchase->reference_no }}</a></td>
                        <td>{{ $purchase->purchase_date->format('d-m-Y') }}</td>
                        <td>{{ $purchase->supplier->name }}</td>
                        <td>{{ $purchase->branch->name }}</td>
                        <td>{{ $purchase->status->label() }}</td>
                        <td class="text-end">{{ money($purchase->grand_total) }}</td>
                        <td class="text-end">{{ money($purchase->balanceDue()) }}</td>
                        <td>{{ $purchase->payment_status->label() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted">No purchases found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $purchases->links() }}
@endsection
