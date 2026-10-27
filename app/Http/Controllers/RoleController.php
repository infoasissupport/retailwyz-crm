<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('can:roles.manage')];
    }

    public function index(): View
    {
        $roles = Role::query()->withCount(['users', 'permissions'])->orderBy('name')->get();

        return view('roles.index', compact('roles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('roles', 'name')->where('guard_name', 'web')],
        ]);

        $role = Role::create(['name' => $data['name'], 'guard_name' => 'web']);
        AuditLog::record('role_created', $role, [], ['name' => $role->name]);
        $this->forgetPermissionCache();

        return redirect()->route('roles.permissions', $role)->with('success', 'Role created. Choose its permissions.');
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $this->guardSuperAdmin($role);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('roles', 'name')->where('guard_name', 'web')->ignore($role)],
        ]);

        $old = $role->name;
        $role->update(['name' => $data['name']]);
        AuditLog::record('role_renamed', $role, ['name' => $old], ['name' => $role->name]);
        $this->forgetPermissionCache();

        return redirect()->route('roles.index')->with('success', 'Role renamed.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->guardSuperAdmin($role);

        if ($role->users()->exists()) {
            return back()->with('error', 'This role is assigned to users and cannot be deleted.');
        }

        AuditLog::record('role_deleted', $role, ['name' => $role->name], []);
        $role->delete();
        $this->forgetPermissionCache();

        return redirect()->route('roles.index')->with('success', 'Role deleted.');
    }

    public function permissions(Role $role): View
    {
        return view('roles.permissions', [
            'role' => $role,
            'modules' => Permissions::MODULES,
            'granted' => $role->permissions()->pluck('name')->all(),
            'locked' => $role->name === User::SUPER_ADMIN_ROLE,
        ]);
    }

    public function updatePermissions(Request $request, Role $role): RedirectResponse
    {
        $this->guardSuperAdmin($role);

        $data = $request->validate([
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::in(Permissions::all())],
        ]);

        $old = $role->permissions()->pluck('name')->sort()->values()->all();
        $role->syncPermissions($data['permissions'] ?? []);
        AuditLog::record('role_permissions_updated', $role, ['permissions' => $old], ['permissions' => collect($data['permissions'] ?? [])->sort()->values()->all()]);
        $this->forgetPermissionCache();

        return redirect()->route('roles.permissions', $role)->with('success', 'Permissions updated.');
    }

    /** The Super Admin role is defined by code (Gate::before) and must stay intact. */
    private function guardSuperAdmin(Role $role): void
    {
        abort_if($role->name === User::SUPER_ADMIN_ROLE, 403, 'The Super Admin role cannot be modified.');
    }

    private function forgetPermissionCache(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
