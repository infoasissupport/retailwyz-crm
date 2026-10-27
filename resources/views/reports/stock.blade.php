@extends('layouts.app', ['breadcrumbs' => ['Reports' => route('reports.index'), 'Stock' => null]])

@section('title', 'Stock report')

@section('content')
    @php $showCost = auth()->user()->can('products.view-cost'); @endphp

    @if ($showCost)
        <p>Total stock value at cost: <strong>{{ money($totalValue) }}</strong></p>
    @endif

    <h2 class="h6">Low stock ({{ $lowStock->count() }})</h2>
    <table class="table table-sm mb-4" style="max-width: 720px">
        <thead><tr><th>SKU</th><th>Product</th><th class="text-end">On hand</th><th class="text-end">Minimum</th></tr></thead>
        <tbody>
            @forelse ($lowStock as $row)
                <tr>
                    <td>{{ $row->sku }}</td>
                    <td><a href="{{ route('products.show', $row->id) }}">{{ $row->name }}</a></td>
                    <td class="text-end text-danger">{{ qty($row->quantity) }}</td>
                    <td class="text-end">{{ qty($row->min_stock) }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-muted">No products below minimum stock.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2 class="h6">Stock on hand</h2>
    <div class="table-responsive">
        <table class="table table-sm">
            <thead>
                <tr>
                    <th>SKU</th><th>Product</th><th>Variant</th><th>Branch</th><th class="text-end">Qty</th>
                    @if ($showCost)<th class="text-end">Unit cost</th><th class="text-end">Value</th>@endif
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td>{{ $row->sku }}</td>
                        <td>{{ $row->name }}</td>
                        <td>{{ $row->variant ?? '—' }}</td>
                        <td>{{ $row->branch }}</td>
                        <td class="text-end">{{ qty($row->quantity) }}</td>
                        @if ($showCost)
                            <td class="text-end">{{ money($row->cost_price) }}</td>
                            <td class="text-end">{{ money(\App\Support\Money::round($row->value)) }}</td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted">No stock recorded.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $rows->links() }}
@endsection
