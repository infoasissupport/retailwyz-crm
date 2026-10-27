@extends('layouts.app', ['breadcrumbs' => ['Inventory' => null]])

@section('title', 'Stock levels')

@section('content')
    <form method="GET" class="fw-grid gap-2">
        <div class="fw-span-md-4">
            <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search product name, SKU or barcode">
        </div>
        @if ($branches->isNotEmpty())
            <div class="fw-span-md-3">
                <select name="branch_id" class="form-select">
                    <option value="">All branches</option>
                    @foreach ($branches as $id => $name)
                        <option value="{{ $id }}" @selected(request('branch_id') == $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <div class="fw-span-md-2 form-check pt-2 ms-2">
            <input type="checkbox" name="low_stock" value="1" id="low_stock" class="form-check-input" @checked(request('low_stock'))>
            <label for="low_stock" class="form-check-label">Low stock only</label>
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
                    <th>Product</th>
                    <th>Variant</th>
                    <th>Branch</th>
                    <th class="text-end">On hand</th>
                    <th class="text-end">Minimum</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($levels as $level)
                    <tr>
                        <td>{{ $level->product->sku }}</td>
                        <td><a href="{{ route('products.show', $level->product_id) }}">{{ $level->product->name }}</a></td>
                        <td>{{ $level->variant?->name ?? '—' }}</td>
                        <td>{{ $level->branch->name }}</td>
                        <td @class(['text-end', 'text-danger' => (float) $level->quantity <= (float) $level->product->min_stock])>
                            {{ qty($level->quantity) }} {{ $level->product->unit->short_name }}
                        </td>
                        <td class="text-end">{{ qty($level->product->min_stock) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted">No stock recorded.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $levels->links() }}
@endsection
