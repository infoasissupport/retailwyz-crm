@extends('layouts.app', ['breadcrumbs' => ['Brands' => null]])

@section('title', 'Brands')

@section('page-actions')
    <a href="{{ route('brands.create') }}" class="btn btn-sm btn-primary">New brand</a>
@endsection

@section('content')
    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-4"><input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search brands"></div>
        <div class="col-md-2"><button type="submit" class="btn btn-outline-secondary w-100">Search</button></div>
    </form>

    <div class="table-responsive">
        <table class="table table-sm align-middle">
            <thead><tr><th>Name</th><th class="text-end">Products</th><th>Active</th><th></th></tr></thead>
            <tbody>
                @forelse ($brands as $brand)
                    <tr>
                        <td>{{ $brand->name }}</td>
                        <td class="text-end">{{ $brand->products_count }}</td>
                        <td>{{ $brand->is_active ? 'Yes' : 'No' }}</td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('brands.edit', $brand) }}" class="btn btn-sm btn-link">Edit</a>
                            <form method="POST" action="{{ route('brands.destroy', $brand) }}" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-link text-danger" data-confirm="Delete this brand?">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted">No brands found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $brands->links() }}
@endsection
