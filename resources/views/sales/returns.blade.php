@extends('layouts.app', ['breadcrumbs' => ['Sales' => route('sales.index'), 'Returns' => null]])

@section('title', 'Sales returns')

@section('content')
    {{-- Returns are recorded from the sale's page (sales.show). --}}
    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-2"><input type="date" name="from" value="{{ request('from') }}" class="form-control" aria-label="From"></div>
        <div class="col-md-2"><input type="date" name="to" value="{{ request('to') }}" class="form-control" aria-label="To"></div>
        <div class="col-md-2"><button type="submit" class="btn btn-outline-secondary w-100">Filter</button></div>
    </form>

    <div class="table-responsive">
        <table class="table table-sm align-middle">
            <thead>
                <tr>
                    <th>Reference</th><th>Date</th><th>Invoice</th><th>Customer</th><th>Branch</th>
                    <th>Restocked</th><th class="text-end">Total</th><th>By</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($returns as $return)
                    <tr>
                        <td>{{ $return->reference_no }}</td>
                        <td>{{ $return->return_date->format('d-m-Y') }}</td>
                        <td><a href="{{ route('sales.show', $return->sale_id) }}">{{ $return->sale->invoice_no }}</a></td>
                        <td>{{ $return->customer?->name ?? 'Walk-in' }}</td>
                        <td>{{ $return->branch->name }}</td>
                        <td>{{ $return->restock ? 'Yes' : 'No' }}</td>
                        <td class="text-end">{{ money($return->grand_total) }}</td>
                        <td>{{ $return->creator?->name }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted">No returns.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $returns->links() }}
@endsection
