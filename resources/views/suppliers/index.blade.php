@extends('layouts.app', ['breadcrumbs' => ['Suppliers' => null]])

@section('title', 'Suppliers')

@section('page-actions')
    @can('suppliers.manage')
        <a href="{{ route('suppliers.create') }}" class="btn btn-sm btn-primary">New supplier</a>
    @endcan
@endsection

@section('content')
    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-4"><input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search name, code or phone"></div>
        <div class="col-md-2"><button type="submit" class="btn btn-outline-secondary w-100">Search</button></div>
    </form>

    <div class="table-responsive">
        <table class="table table-sm align-middle">
            <thead><tr><th>Code</th><th>Name</th><th>Contact</th><th>Phone</th><th>Active</th></tr></thead>
            <tbody>
                @forelse ($suppliers as $supplier)
                    <tr>
                        <td>{{ $supplier->code }}</td>
                        <td><a href="{{ route('suppliers.show', $supplier) }}">{{ $supplier->name }}</a></td>
                        <td>{{ $supplier->contact_person }}</td>
                        <td>{{ $supplier->phone }}</td>
                        <td>{{ $supplier->is_active ? 'Yes' : 'No' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted">No suppliers found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $suppliers->links() }}
@endsection
