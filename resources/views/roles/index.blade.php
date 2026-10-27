@extends('layouts.app', ['breadcrumbs' => ['Roles & permissions' => null]])

@section('title', 'Roles & permissions')

@section('content')
    <form method="POST" action="{{ route('roles.store') }}" class="fw-grid gap-2 mb-4">
        @csrf
        <div class="fw-span-md-4">
            <input type="text" name="name" value="{{ old('name') }}" class="form-control" placeholder="New role name" required maxlength="100">
        </div>
        <div class="fw-span-md-2"><button type="submit" class="btn btn-primary w-100">Add role</button></div>
    </form>

    <div class="table-responsive">
        <table class="table table-sm align-middle">
            <thead><tr><th>Role</th><th class="text-end">Users</th><th class="text-end">Permissions</th><th></th></tr></thead>
            <tbody>
                @foreach ($roles as $role)
                    @php $isSuperAdmin = $role->name === App\Models\User::SUPER_ADMIN_ROLE; @endphp
                    <tr>
                        <td>
                            @if ($isSuperAdmin)
                                {{ $role->name }} <span class="small text-muted">(all permissions)</span>
                            @else
                                <form method="POST" action="{{ route('roles.update', $role) }}" class="d-flex gap-2">
                                    @csrf
                                    @method('PUT')
                                    <input type="text" name="name" value="{{ $role->name }}" class="form-control form-control-sm" required maxlength="100">
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">Rename</button>
                                </form>
                            @endif
                        </td>
                        <td class="text-end">{{ $role->users_count }}</td>
                        <td class="text-end">{{ $isSuperAdmin ? 'All' : $role->permissions_count }}</td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('roles.permissions', $role) }}" class="btn btn-sm btn-link">Permissions</a>
                            @unless ($isSuperAdmin)
                                <form method="POST" action="{{ route('roles.destroy', $role) }}" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-link text-danger" data-confirm="Delete this role?">Delete</button>
                                </form>
                            @endunless
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
