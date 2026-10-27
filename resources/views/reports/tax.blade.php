@extends('layouts.app', ['breadcrumbs' => ['Reports' => route('reports.index'), 'Tax' => null]])

@section('title', 'Tax report')

@section('content')
    @include('reports.partials.period-filter')

    <div class="table-responsive">
        <table class="table table-sm">
            <thead>
                <tr>
                    <th>Rate</th>
                    <th class="text-end">Sales taxable</th><th class="text-end">Output tax</th>
                    <th class="text-end">Purchases taxable</th><th class="text-end">Input tax</th>
                    <th class="text-end">Net</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($report['rows'] as $row)
                    <tr>
                        <td>{{ qty($row->rate) }}%</td>
                        <td class="text-end">{{ money($row->output_taxable) }}</td>
                        <td class="text-end">{{ money($row->output_tax) }}</td>
                        <td class="text-end">{{ money($row->input_taxable) }}</td>
                        <td class="text-end">{{ money($row->input_tax) }}</td>
                        <td class="text-end">{{ money($row->net) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-muted">No taxable transactions in this period.</td></tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="fw-semibold">
                    <td>Total</td><td></td><td class="text-end">{{ money($report['output_total']) }}</td>
                    <td></td><td class="text-end">{{ money($report['input_total']) }}</td>
                    <td class="text-end">{{ money($report['net_payable']) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
    <p class="small text-muted">Output tax is net of sales returns; input tax covers received goods only, net of purchase returns.</p>
@endsection
