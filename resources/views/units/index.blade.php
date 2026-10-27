@extends('layouts.app', ['breadcrumbs' => ['Units' => null]])

@section('title', 'Units')

@section('page-actions')
    <a href="{{ route('units.create') }}" class="btn btn-sm btn-primary">New unit</a>
@endsection

@section('content')
    <div class="table-responsive">
        <table class="table table-sm align-middle">
            <thead><tr><th>Name</th><th>Short name</th><th>Decimal qty</th><th class="text-end">Products</th><th>Active</th><th></th></tr></thead>
            <tbody>
                @forelse ($units as $unit)
                    <tr>
                        <td>{{ $unit->name }}</td>
                        <td>{{ $unit->short_name }}</td>
                        <td>{{ $unit->allow_decimal ? 'Yes' : 'No' }}</td>
                        <td class="text-end">{{ $unit->products_count }}</td>
                        <td>{{ $unit->is_active ? 'Yes' : 'No' }}</td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('units.edit', $unit) }}" class="btn btn-sm btn-link">Edit</a>
                            <form method="POST" action="{{ route('units.destroy', $unit) }}" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-link text-danger" data-confirm="Delete this unit?">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted">No units found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $units->links() }}
@endsection
