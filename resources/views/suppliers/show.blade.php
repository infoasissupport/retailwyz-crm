@extends('layouts.app', ['breadcrumbs' => ['Suppliers' => route('suppliers.index'), $supplier->name => null]])

@section('title', $supplier->name)

@section('page-actions')
    @can('suppliers.manage')
        <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-sm btn-outline-primary">Edit</a>
        <form method="POST" action="{{ route('suppliers.destroy', $supplier) }}" class="d-inline">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="Delete this supplier?">Delete</button>
        </form>
    @endcan
@endsection

@section('content')
    <div class="row g-3">
        <div class="col-lg-5">
            <dl class="row mb-0">
                <dt class="col-sm-4">Code</dt><dd class="col-sm-8">{{ $supplier->code }}</dd>
                <dt class="col-sm-4">Contact</dt><dd class="col-sm-8">{{ $supplier->contact_person ?? '—' }}</dd>
                <dt class="col-sm-4">Phone</dt><dd class="col-sm-8">{{ $supplier->phone ?? '—' }}</dd>
                <dt class="col-sm-4">Email</dt><dd class="col-sm-8">{{ $supplier->email ?? '—' }}</dd>
                <dt class="col-sm-4">Tax number</dt><dd class="col-sm-8">{{ $supplier->tax_number ?? '—' }}</dd>
                <dt class="col-sm-4">Address</dt><dd class="col-sm-8">{{ $supplier->address ?? '—' }}</dd>
                <dt class="col-sm-4">Opening balance</dt><dd class="col-sm-8">{{ money($supplier->opening_balance) }}</dd>
                <dt class="col-sm-4">Payable</dt><dd class="col-sm-8">{{ money($payable) }}</dd>
                <dt class="col-sm-4">Active</dt><dd class="col-sm-8">{{ $supplier->is_active ? 'Yes' : 'No' }}</dd>
            </dl>
        </div>

        <div class="col-lg-7">
            <h2 class="h6">Recent purchases</h2>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead><tr><th>Reference</th><th>Date</th><th>Branch</th><th>Status</th><th class="text-end">Total</th><th class="text-end">Due</th></tr></thead>
                    <tbody>
                        @forelse ($purchases as $purchase)
                            <tr>
                                <td><a href="{{ route('purchases.show', $purchase) }}">{{ $purchase->reference_no }}</a></td>
                                <td>{{ $purchase->purchase_date->format('d-m-Y') }}</td>
                                <td>{{ $purchase->branch->name }}</td>
                                <td>{{ $purchase->status->label() }} / {{ $purchase->payment_status->label() }}</td>
                                <td class="text-end">{{ money($purchase->grand_total) }}</td>
                                <td class="text-end">{{ money($purchase->balanceDue()) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-muted">No purchases yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
