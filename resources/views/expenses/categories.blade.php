@extends('layouts.app', ['breadcrumbs' => ['Expenses' => route('expenses.index'), 'Categories' => null]])

@section('title', 'Expense categories')

@section('content')
    @can('expenses.manage')
        <form method="POST" action="{{ route('expenses.categories.store') }}" class="row g-2 align-items-end mb-4">
            @csrf
            <div class="col-md-4"><x-form.input name="name" label="New category" required maxlength="255" /></div>
            <div class="col-md-6"><x-form.input name="description" label="Description" maxlength="1000" /></div>
            <div class="col-md-2 mb-3"><button type="submit" class="btn btn-primary w-100">Add</button></div>
        </form>
    @endcan

    <table class="table table-sm align-middle">
        <thead><tr><th>Name</th><th>Description</th><th>Active</th><th class="text-end">Expenses</th><th></th></tr></thead>
        <tbody>
            @forelse ($categories as $category)
                <tr>
                    @can('expenses.manage')
                        <td colspan="3">
                            <form method="POST" action="{{ route('expenses.categories.update', $category) }}" class="row g-2" id="category-{{ $category->id }}">
                                @csrf
                                @method('PUT')
                                <div class="col-md-4"><input type="text" name="name" value="{{ $category->name }}" class="form-control form-control-sm" required maxlength="255" aria-label="Name"></div>
                                <div class="col-md-5"><input type="text" name="description" value="{{ $category->description }}" class="form-control form-control-sm" maxlength="1000" aria-label="Description"></div>
                                <div class="col-md-1">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" name="is_active" value="1" class="form-check-input" @checked($category->is_active) aria-label="Active">
                                </div>
                                <div class="col-md-2"><button type="submit" class="btn btn-sm btn-outline-primary">Save</button></div>
                            </form>
                        </td>
                    @else
                        <td>{{ $category->name }}</td>
                        <td>{{ $category->description }}</td>
                        <td>{{ $category->is_active ? 'Yes' : 'No' }}</td>
                    @endcan
                    <td class="text-end">{{ $category->expenses_count }}</td>
                    <td class="text-end">
                        @can('expenses.manage')
                            @if ($category->expenses_count === 0)
                                <form method="POST" action="{{ route('expenses.categories.destroy', $category) }}" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-link text-danger" data-confirm="Delete this category?">Delete</button>
                                </form>
                            @endif
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted">No categories.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
