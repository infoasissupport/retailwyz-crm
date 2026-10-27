@extends('layouts.app', ['breadcrumbs' => ['Users' => route('users.index'), $user->name => null]])

@section('title', $user->name)

@section('page-actions')
    @can('update', $user)
        <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-outline-primary">Edit</a>
    @endcan
    @if ($user->is_active && ! $user->is(auth()->user()))
        @can('delete', $user)
            <form method="POST" action="{{ route('users.destroy', $user) }}" class="d-inline">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="Deactivate this user?">Deactivate</button>
            </form>
        @endcan
    @endif
@endsection

@section('content')
    <div class="row g-3">
        <div class="col-lg-5">
            <dl class="row mb-0">
                <dt class="col-sm-4">Email</dt><dd class="col-sm-8">{{ $user->email }}</dd>
                <dt class="col-sm-4">Phone</dt><dd class="col-sm-8">{{ $user->phone ?? '—' }}</dd>
                <dt class="col-sm-4">Branch</dt><dd class="col-sm-8">{{ $user->branch?->name ?? '—' }}</dd>
                <dt class="col-sm-4">Roles</dt><dd class="col-sm-8">{{ $user->roles->pluck('name')->join(', ') ?: '—' }}</dd>
                <dt class="col-sm-4">Active</dt><dd class="col-sm-8">{{ $user->is_active ? 'Yes' : 'No' }}</dd>
                <dt class="col-sm-4">Last login</dt><dd class="col-sm-8">{{ $user->last_login_at?->format('d-m-Y H:i') ?? '—' }}</dd>
            </dl>
        </div>

        <div class="col-lg-7">
            <h2 class="h6">Recent activity</h2>
            <table class="table table-sm">
                <thead><tr><th>When</th><th>Event</th><th>Record</th></tr></thead>
                <tbody>
                    @forelse ($activity as $log)
                        <tr>
                            <td>{{ $log->created_at->format('d-m-Y H:i') }}</td>
                            <td>{{ $log->event }}</td>
                            <td>{{ $log->auditable_type }} {{ $log->auditable_id ? '#'.$log->auditable_id : '' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-muted">No activity recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
