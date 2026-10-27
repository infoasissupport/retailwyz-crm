@extends('layouts.app', ['breadcrumbs' => ['Products' => route('products.index'), $product->name => null]])

@section('title', $product->name)

@section('page-actions')
    @can('products.update')
        <a href="{{ route('products.edit', $product) }}" class="btn btn-sm btn-outline-primary">Edit</a>
    @endcan
    @can('products.delete')
        <form method="POST" action="{{ route('products.destroy', $product) }}" class="d-inline">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="Delete this product?">Delete</button>
        </form>
    @endcan
@endsection

@section('content')
    <div class="row g-3">
        <div class="col-lg-6">
            <dl class="row mb-0">
                <dt class="col-sm-4">SKU</dt><dd class="col-sm-8">{{ $product->sku }}</dd>
                <dt class="col-sm-4">Barcode</dt><dd class="col-sm-8">{{ $product->barcode ?? '—' }}</dd>
                <dt class="col-sm-4">Category</dt><dd class="col-sm-8">{{ $product->category->name }}</dd>
                <dt class="col-sm-4">Brand</dt><dd class="col-sm-8">{{ $product->brand?->name ?? '—' }}</dd>
                <dt class="col-sm-4">Unit</dt><dd class="col-sm-8">{{ $product->unit->name }}</dd>
                <dt class="col-sm-4">Selling price</dt><dd class="col-sm-8">{{ money($product->selling_price) }}</dd>
                @can('products.view-cost')
                    <dt class="col-sm-4">Cost price</dt><dd class="col-sm-8">{{ money($product->cost_price) }}</dd>
                @endcan
                <dt class="col-sm-4">Tax</dt><dd class="col-sm-8">{{ $product->tax?->name ?? 'None' }}</dd>
                <dt class="col-sm-4">Minimum stock</dt><dd class="col-sm-8">{{ qty($product->min_stock) }}</dd>
                <dt class="col-sm-4">Default supplier</dt><dd class="col-sm-8">{{ $product->defaultSupplier?->name ?? '—' }}</dd>
                <dt class="col-sm-4">Status</dt><dd class="col-sm-8">{{ $product->status->label() }}</dd>
            </dl>

            @if ($product->specifications)
                <h2 class="h6 mt-3">Specifications</h2>
                <dl class="row">
                    @foreach ($product->specifications as $key => $value)
                        <dt class="col-sm-4">{{ $key }}</dt><dd class="col-sm-8">{{ $value }}</dd>
                    @endforeach
                </dl>
            @endif
        </div>

        <div class="col-lg-6">
            <h2 class="h6">Stock by branch</h2>
            <table class="table table-sm">
                <thead><tr><th>Branch</th><th>Variant</th><th class="text-end">On hand</th></tr></thead>
                <tbody>
                    @forelse ($stockLevels as $level)
                        <tr>
                            <td>{{ $level->branch->name }}</td>
                            <td>{{ $level->variant?->name ?? '—' }}</td>
                            <td class="text-end">{{ qty($level->quantity) }} {{ $product->unit->short_name }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-muted">No stock recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>

            @if ($product->has_variants)
                <h2 class="h6 mt-3">Variants</h2>
                <table class="table table-sm">
                    <thead><tr><th>Name</th><th>SKU</th><th class="text-end">Price</th><th>Active</th></tr></thead>
                    <tbody>
                        @foreach ($product->variants as $variant)
                            <tr>
                                <td>{{ $variant->name }}</td>
                                <td>{{ $variant->sku }}</td>
                                <td class="text-end">{{ money($variant->selling_price ?? $product->selling_price) }}</td>
                                <td>{{ $variant->is_active ? 'Yes' : 'No' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    <h2 class="h6 mt-4">Recent stock movements</h2>
    @include('inventory.partials.movements-table', ['movements' => $movements])
@endsection
