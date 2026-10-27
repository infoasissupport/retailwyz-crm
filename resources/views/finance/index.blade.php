@extends('layouts.app', ['breadcrumbs' => ['Finance' => null]])

@section('title', 'Finance')

@section('page-actions')
    <a href="{{ route('finance.transactions') }}" class="btn btn-sm btn-outline-secondary">Ledger</a>
@endsection

@section('content')
    @include('reports.partials.period-filter')

    <div class="row g-3 mb-4">
        @foreach ($accounts as $account => $totals)
            <div class="col-md-4">
                <div class="card"><div class="card-body">
                    <h2 class="h6 text-capitalize">{{ $account }}</h2>
                    <dl class="row mb-0 small">
                        <dt class="col-6">In</dt><dd class="col-6 text-end">{{ money($totals['in']) }}</dd>
                        <dt class="col-6">Out</dt><dd class="col-6 text-end">{{ money($totals['out']) }}</dd>
                        <dt class="col-6">Net</dt><dd class="col-6 text-end">{{ money($totals['net']) }}</dd>
                    </dl>
                </div></div>
            </div>
        @endforeach
        <div class="col-md-4">
            <div class="card"><div class="card-body">
                <h2 class="h6">All accounts</h2>
                <dl class="row mb-0 small">
                    <dt class="col-6">In</dt><dd class="col-6 text-end">{{ money($totalIn) }}</dd>
                    <dt class="col-6">Out</dt><dd class="col-6 text-end">{{ money($totalOut) }}</dd>
                </dl>
            </div></div>
        </div>
    </div>

    <h2 class="h6">By transaction type</h2>
    <table class="table table-sm">
        <thead><tr><th>Type</th><th>Direction</th><th class="text-end">Entries</th><th class="text-end">Total</th></tr></thead>
        <tbody>
            @forelse ($byType as $row)
                <tr>
                    <td>{{ ucwords(str_replace('_', ' ', $row->type)) }}</td>
                    <td>{{ $row->direction === 'in' ? 'In' : 'Out' }}</td>
                    <td class="text-end">{{ $row->entries }}</td>
                    <td class="text-end">{{ money($row->total) }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-muted">No transactions in this period.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
