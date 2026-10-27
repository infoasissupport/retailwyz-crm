@extends('layouts.app', ['breadcrumbs' => ['Users' => null]])

@section('title', 'Users')

@section('page-actions')
    @can('create', App\Models\User::class)
        <a href="{{ route('users.create') }}" class="btn btn-sm btn-primary">New user</a>
    @endcan
@endsection

@section('content')
    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-4"><input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search name or email"></div>
        <div class="col-md-2"><button type="submit" class="btn btn-outline-secondary w-100">Search</button></div>
    </form>

    <div class="table-responsive">
        <table class="table table-sm align-middle">
            <thead><tr><th>Name</th><th>Email</th><th>Branch</th><th>Roles</th><th>Active</th><th>Last login</th></tr></thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td><a href="{{ route('users.show', $user) }}">{{ $user->name }}</a></td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->branch?->name ?? '—' }}</td>
                        <td>{{ $user->roles->pluck('name')->join(', ') }}</td>
                        <td>{{ $user->is_active ? 'Yes' : 'No' }}</td>
                        <td>{{ $user->last_login_at?->format('d-m-Y H:i') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted">No users found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $users->links() }}
@endsection
