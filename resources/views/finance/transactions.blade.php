@extends('layouts.app', ['breadcrumbs' => ['Finance' => route('finance.index'), 'Ledger' => null]])

@section('title', 'Financial transactions')

@section('content')
    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-2">
            <select name="type" class="form-select" aria-label="Type">
                <option value="">All types</option>
                @foreach ($types as $type)
                    <option value="{{ $type }}" @selected(request('type') === $type)>{{ ucwords(str_replace('_', ' ', $type)) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <select name="account" class="form-select" aria-label="Account">
                <option value="">All accounts</option>
                <option value="cash" @selected(request('account') === 'cash')>Cash</option>
                <option value="bank" @selected(request('account') === 'bank')>Bank</option>
            </select>
        </div>
        <div class="col-md-2">
            <select name="direction" class="form-select" aria-label="Direction">
                <option value="">In &amp; out</option>
                <option value="in" @selected(request('direction') === 'in')>In</option>
                <option value="out" @selected(request('direction') === 'out')>Out</option>
            </select>
        </div>
        <div class="col-md-2"><input type="date" name="from" value="{{ request('from') }}" class="form-control" aria-label="From"></div>
        <div class="col-md-2"><input type="date" name="to" value="{{ request('to') }}" class="form-control" aria-label="To"></div>
        <div class="col-md-2"><button type="submit" class="btn btn-outline-secondary w-100">Filter</button></div>
    </form>

    <div class="table-responsive">
        <table class="table table-sm">
            <thead><tr><th>Date</th><th>Type</th><th>Description</th><th>Account</th><th>Method</th><th class="text-end">In</th><th class="text-end">Out</th><th>Branch</th><th>By</th></tr></thead>
            <tbody>
                @forelse ($transactions as $transaction)
                    <tr>
                        <td>{{ $transaction->transaction_date->format('d-m-Y') }}</td>
                        <td>{{ ucwords(str_replace('_', ' ', $transaction->type)) }}</td>
                        <td>{{ $transaction->description }}</td>
                        <td class="text-capitalize">{{ $transaction->account }}</td>
                        <td>{{ $transaction->method->label() }}</td>
                        <td class="text-end">{{ $transaction->direction === 'in' ? money($transaction->amount) : '' }}</td>
                        <td class="text-end">{{ $transaction->direction === 'out' ? money($transaction->amount) : '' }}</td>
                        <td>{{ $transaction->branch->name }}</td>
                        <td>{{ $transaction->creator?->name }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted">No transactions found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $transactions->links() }}
@endsection
