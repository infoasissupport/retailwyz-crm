@extends('layouts.app', ['breadcrumbs' => ['Payments' => null]])

@section('title', 'Payments')

@section('content')
    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-2">
            <select name="direction" class="form-select">
                <option value="">In &amp; out</option>
                <option value="in" @selected(request('direction') === 'in')>Received</option>
                <option value="out" @selected(request('direction') === 'out')>Paid</option>
            </select>
        </div>
        <div class="col-md-2">
            <select name="method" class="form-select">
                <option value="">All methods</option>
                @foreach ($methods as $value => $label)
                    <option value="{{ $value }}" @selected(request('method') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3"><input type="date" name="from" value="{{ request('from') }}" class="form-control" aria-label="From"></div>
        <div class="col-md-3"><input type="date" name="to" value="{{ request('to') }}" class="form-control" aria-label="To"></div>
        <div class="col-md-2"><button type="submit" class="btn btn-outline-secondary w-100">Filter</button></div>
    </form>

    <div class="table-responsive">
        <table class="table table-sm align-middle">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Payment no</th>
                    <th>Document</th>
                    <th>Party</th>
                    <th>Direction</th>
                    <th>Method</th>
                    <th class="text-end">Amount</th>
                    <th>Branch</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($payments as $payment)
                    <tr>
                        <td>{{ $payment->paid_on->format('d-m-Y') }}</td>
                        <td>{{ $payment->payment_no }}</td>
                        <td>
                            @if ($payment->payable instanceof \App\Models\Sale)
                                <a href="{{ route('sales.show', $payment->payable) }}">{{ $payment->payable->invoice_no }}</a>
                            @elseif ($payment->payable instanceof \App\Models\Purchase)
                                <a href="{{ route('purchases.show', $payment->payable) }}">{{ $payment->payable->reference_no }}</a>
                            @endif
                        </td>
                        <td>{{ $payment->customer?->name ?? $payment->supplier?->name ?? 'Walk-in' }}</td>
                        <td>{{ $payment->direction === 'in' ? 'Received' : 'Paid' }}</td>
                        <td>{{ $payment->method->label() }}</td>
                        <td class="text-end">{{ money($payment->amount) }}</td>
                        <td>{{ $payment->branch->name }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted">No payments found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $payments->links() }}
@endsection
