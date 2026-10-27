@extends('layouts.app', ['breadcrumbs' => ['Roles & permissions' => route('roles.index'), $role->name => null]])

@section('title', 'Permissions: '.$role->name)

@section('content')
    @if ($locked)
        <p class="text-muted">The Super Admin role always has every permission and cannot be edited.</p>
    @endif

    <form method="POST" action="{{ route('roles.permissions.update', $role) }}">
        @csrf
        @method('PUT')

        <div class="fw-grid gap-3">
            @foreach ($modules as $module => $permissions)
                <div class="fw-span-md-6 fw-span-xl-4">
                    <fieldset class="border rounded p-3 h-100">
                        <legend class="h6 float-none w-auto px-1 mb-2">{{ $module }}</legend>
                        @foreach ($permissions as $permission)
                            <div class="form-check">
                                <input type="checkbox" name="permissions[]" value="{{ $permission }}" id="perm_{{ $permission }}" class="form-check-input"
                                       @checked($locked || in_array($permission, old('permissions', $granted), true)) @disabled($locked)>
                                <label for="perm_{{ $permission }}" class="form-check-label">{{ $permission }}</label>
                            </div>
                        @endforeach
                    </fieldset>
                </div>
            @endforeach
        </div>

        @unless ($locked)
            <button type="submit" class="btn btn-primary mt-3">Save permissions</button>
        @endunless
        <a href="{{ route('roles.index') }}" class="btn btn-link mt-3">Back</a>
    </form>
@endsection
