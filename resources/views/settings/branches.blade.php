@extends('layouts.app', ['breadcrumbs' => ['Settings' => null, 'Branches' => null]])

@section('title', 'Branches')

@section('content')
    @include('settings.partials.nav')

    <h2 class="h6">Add branch</h2>
    <form method="POST" action="{{ route('settings.branches.store') }}" class="fw-grid gap-2 mb-4">
        @csrf
        <div class="fw-span-md-2"><input type="text" name="code" value="{{ old('code') }}" class="form-control" placeholder="Code" required maxlength="20"></div>
        <div class="fw-span-md-3"><input type="text" name="name" value="{{ old('name') }}" class="form-control" placeholder="Name" required maxlength="255"></div>
        <div class="fw-span-md-2"><input type="text" name="phone" value="{{ old('phone') }}" class="form-control" placeholder="Phone" maxlength="30"></div>
        <div class="fw-span-md-3"><input type="text" name="tax_number" value="{{ old('tax_number') }}" class="form-control" placeholder="Tax number" maxlength="50"></div>
        <input type="hidden" name="is_active" value="1">
        <div class="fw-span-md-2"><button type="submit" class="btn btn-primary w-100">Add</button></div>
    </form>

    <div class="table-responsive">
        <table class="table table-sm align-middle">
            <thead><tr><th>Code</th><th>Name</th><th>Phone</th><th>Tax number</th><th>Active</th><th class="text-end">Users</th><th></th></tr></thead>
            <tbody>
                @forelse ($branches as $branch)
                    <tr>
                        <td colspan="5">
                            <form method="POST" action="{{ route('settings.branches.update', $branch) }}" id="branch-{{ $branch->id }}" class="fw-grid gap-1">
                                @csrf
                                @method('PUT')
                                <div class="fw-span-md-2"><input type="text" name="code" value="{{ $branch->code }}" class="form-control form-control-sm" required maxlength="20"></div>
                                <div class="fw-span-md-4"><input type="text" name="name" value="{{ $branch->name }}" class="form-control form-control-sm" required maxlength="255"></div>
                                <div class="fw-span-md-2"><input type="text" name="phone" value="{{ $branch->phone }}" class="form-control form-control-sm" maxlength="30"></div>
                                <div class="fw-span-md-3"><input type="text" name="tax_number" value="{{ $branch->tax_number }}" class="form-control form-control-sm" maxlength="50"></div>
                                <div class="fw-span-md-1">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" name="is_active" value="1" class="form-check-input" @checked($branch->is_active) title="Active">
                                </div>
                            </form>
                        </td>
                        <td class="text-end">{{ $branch->users_count }}</td>
                        <td class="text-end text-nowrap">
                            <button type="submit" form="branch-{{ $branch->id }}" class="btn btn-sm btn-link">Save</button>
                            <form method="POST" action="{{ route('settings.branches.destroy', $branch) }}" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-link text-danger" data-confirm="Delete this branch?">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted">No branches.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $branches->links() }}
@endsection
