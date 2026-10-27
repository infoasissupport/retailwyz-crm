@extends('layouts.app', ['breadcrumbs' => ['Categories' => null]])

@section('title', 'Categories')

@section('page-actions')
    <a href="{{ route('categories.create') }}" class="btn btn-sm btn-primary">New category</a>
@endsection

@section('content')
    <form method="GET" class="fw-grid gap-2">
        <div class="fw-span-md-4"><input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search categories"></div>
        <div class="fw-span-md-2"><button type="submit" class="btn btn-outline-secondary w-100">Search</button></div>
    </form>

    <div class="table-responsive">
        <table class="table table-sm align-middle">
            <thead><tr><th>Name</th><th>Parent</th><th class="text-end">Products</th><th>Active</th><th></th></tr></thead>
            <tbody>
                @forelse ($categories as $category)
                    <tr>
                        <td>{{ $category->name }}</td>
                        <td>{{ $category->parent?->name ?? '—' }}</td>
                        <td class="text-end">{{ $category->products_count }}</td>
                        <td>{{ $category->is_active ? 'Yes' : 'No' }}</td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('categories.edit', $category) }}" class="btn btn-sm btn-link">Edit</a>
                            <form method="POST" action="{{ route('categories.destroy', $category) }}" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-link text-danger" data-confirm="Delete this category?">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted">No categories found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $categories->links() }}
@endsection
