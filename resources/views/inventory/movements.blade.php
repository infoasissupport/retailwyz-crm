@extends('layouts.app', ['breadcrumbs' => ['Inventory' => route('inventory.index'), 'Movements' => null]])

@section('title', 'Stock movements')

@section('content')
    <form method="GET" class="fw-grid gap-2">
        <div class="fw-span-md-3">
            <select name="product_id" class="form-select">
                <option value="">All products</option>
                @foreach ($products as $id => $name)
                    <option value="{{ $id }}" @selected(request('product_id') == $id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div class="fw-span-md-2">
            <select name="type" class="form-select">
                <option value="">All types</option>
                @foreach ($types as $value => $label)
                    <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        @if ($branches->isNotEmpty())
            <div class="fw-span-md-2">
                <select name="branch_id" class="form-select">
                    <option value="">All branches</option>
                    @foreach ($branches as $id => $name)
                        <option value="{{ $id }}" @selected(request('branch_id') == $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <div class="fw-span-md-2"><input type="date" name="from" value="{{ request('from') }}" class="form-control" aria-label="From"></div>
        <div class="fw-span-md-2"><input type="date" name="to" value="{{ request('to') }}" class="form-control" aria-label="To"></div>
        <div class="fw-span-md-1"><button type="submit" class="btn btn-outline-secondary w-100">Filter</button></div>
    </form>

    @include('inventory.partials.movements-table', ['movements' => $movements])

    {{ $movements->links() }}
@endsection
