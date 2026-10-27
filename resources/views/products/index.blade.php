@extends('layouts.app', ['breadcrumbs' => ['Products' => null]])

@section('title', 'Products')

@section('page-actions')
    @can('products.create')
        <a href="{{ route('products.create') }}" class="btn btn-sm btn-primary">New product</a>
    @endcan
@endsection

@section('content')
    <form method="GET" class="fw-grid gap-2">
        <div class="fw-span-md-4">
            <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search name, SKU or barcode">
        </div>
        <div class="fw-span-md-3">
            <select name="category_id" class="form-select">
                <option value="">All categories</option>
                @foreach ($categories as $id => $name)
                    <option value="{{ $id }}" @selected(request('category_id') == $id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div class="fw-span-md-3">
            <select name="brand_id" class="form-select">
                <option value="">All brands</option>
                @foreach ($brands as $id => $name)
                    <option value="{{ $id }}" @selected(request('brand_id') == $id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div class="fw-span-md-2">
            <button type="submit" class="btn btn-outline-secondary w-100">Filter</button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-sm align-middle">
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Brand</th>
                    <th class="text-end">Price</th>
                    <th class="text-end">Stock</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                    <tr>
                        <td>{{ $product->sku }}</td>
                        <td><a href="{{ route('products.show', $product) }}">{{ $product->name }}</a></td>
                        <td>{{ $product->category->name }}</td>
                        <td>{{ $product->brand?->name }}</td>
                        <td class="text-end">{{ money($product->selling_price) }}</td>
                        <td @class(['text-end', 'text-danger' => $product->track_stock && (float) $product->stock_on_hand <= (float) $product->min_stock])>
                            {{ $product->track_stock ? qty($product->stock_on_hand ?? 0).' '.$product->unit->short_name : '—' }}
                        </td>
                        <td>{{ $product->status->label() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted">No products found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $products->links() }}
@endsection
