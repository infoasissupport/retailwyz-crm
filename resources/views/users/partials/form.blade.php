{{-- Shared by users.create and users.edit. Expects $user, $branches and $roles (names). --}}
@php $assigned = old('roles', $user->exists ? $user->roles->pluck('name')->all() : []); @endphp

<div class="row">
    <div class="col-md-6"><x-form.input name="name" label="Name" :value="$user->name" required maxlength="255" /></div>
    <div class="col-md-6"><x-form.input name="email" label="Email" type="email" :value="$user->email" required maxlength="255" autocomplete="off" /></div>
    <div class="col-md-6"><x-form.input name="phone" label="Phone" :value="$user->phone" maxlength="30" /></div>
    <div class="col-md-6"><x-form.select name="branch_id" label="Branch" :options="$branches" :value="$user->branch_id" placeholder="No branch" /></div>

    <div class="col-md-6">
        <x-form.input name="password" label="Password" type="password" autocomplete="new-password"
                      :required="! $user->exists" :help="$user->exists ? 'Leave blank to keep the current password.' : 'At least 8 characters.'" />
    </div>
    <div class="col-md-6"><x-form.input name="password_confirmation" label="Confirm password" type="password" autocomplete="new-password" /></div>
</div>

<fieldset class="mb-3">
    <legend class="h6">Roles</legend>
    @foreach ($roles as $role)
        <div class="form-check">
            <input type="checkbox" name="roles[]" value="{{ $role }}" id="role_{{ $loop->index }}" class="form-check-input" @checked(in_array($role, $assigned, true))>
            <label for="role_{{ $loop->index }}" class="form-check-label">{{ $role }}</label>
        </div>
    @endforeach
    @error('roles')<div class="text-danger small">{{ $message }}</div>@enderror
</fieldset>

<x-form.checkbox name="is_active" label="Active (can log in)" :checked="$user->is_active" />
