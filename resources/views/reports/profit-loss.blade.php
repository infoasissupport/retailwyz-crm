@extends('layouts.app', ['breadcrumbs' => ['Reports' => route('reports.index'), 'Profit & loss' => null]])

@section('title', 'Profit & loss')

@section('content')
    @include('reports.partials.period-filter')

    <table class="table table-sm fw-maxw-560">
        <tbody>
            <tr><td>Sales (net of tax)</td><td class="text-end">{{ money($report['sales_revenue']) }}</td></tr>
            <tr><td>Less: sales returns</td><td class="text-end">{{ money($report['returns_revenue']) }}</td></tr>
            <tr class="fw-semibold"><td>Revenue</td><td class="text-end">{{ money($report['revenue']) }}</td></tr>
            <tr><td>Cost of goods sold</td><td class="text-end">{{ money($report['cogs']) }}</td></tr>
            <tr class="fw-semibold"><td>Gross profit</td><td class="text-end">{{ money($report['gross_profit']) }}</td></tr>
            @foreach ($report['expenses'] as $row)
                <tr><td class="ps-4">{{ $row->category }}</td><td class="text-end">{{ money($row->total) }}</td></tr>
            @endforeach
            <tr><td>Total expenses (net of tax)</td><td class="text-end">{{ money($report['expense_total']) }}</td></tr>
            <tr class="fw-bold"><td>Net profit</td><td class="text-end">{{ money($report['net_profit']) }}</td></tr>
        </tbody>
    </table>
    <p class="small text-muted">Cost of goods sold uses the unit cost recorded on each sale line at the time of sale.</p>
@endsection
