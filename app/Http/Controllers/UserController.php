<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', User::class);
        $actor = $request->user();

        $users = User::query()
            ->with(['branch:id,name', 'roles:id,name'])
            ->unless($actor->canAccessAllBranches(), fn ($q) => $q->where('branch_id', $actor->branch_id ?? 0))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = $request->string('q')->trim()->toString();
                $q->where(fn ($q) => $q->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%"));
            })
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('users.index', compact('users'));
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', User::class);

        return view('users.create', ['user' => new User(['is_active' => true, 'branch_id' => $request->user()->branch_id])] + $this->formOptions($request));
    }

    public function store(UserRequest $request): RedirectResponse
    {
        Gate::authorize('create', User::class);

        $user = DB::transaction(function () use ($request) {
            $user = User::create($request->userData());
            $user->syncRoles($request->validated('roles'));

            return $user;
        });

        return redirect()->route('users.show', $user)->with('success', 'User created.');
    }

    public function show(User $user): View
    {
        Gate::authorize('view', $user);
        $user->load(['branch', 'roles']);

        $activity = AuditLog::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->limit(10)
            ->get();

        return view('users.show', compact('user', 'activity'));
    }

    public function edit(Request $request, User $user): View
    {
        Gate::authorize('update', $user);
        $user->load('roles');

        return view('users.edit', ['user' => $user] + $this->formOptions($request));
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        Gate::authorize('update', $user);

        DB::transaction(function () use ($request, $user) {
            $user->update($request->userData());
            $user->syncRoles($request->validated('roles'));
        });

        return redirect()->route('users.show', $user)->with('success', 'User updated.');
    }

    /**
     * Users are deactivated rather than deleted: their id is referenced by
     * sales, purchases, payments and audit records.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        Gate::authorize('delete', $user);
        $user->update(['is_active' => false]);

        return redirect()->route('users.index')->with('success', 'User deactivated.');
    }

    private function formOptions(Request $request): array
    {
        $actor = $request->user();

        return [
            'branches' => Branch::active()
                ->unless($actor->canAccessAllBranches(), fn ($q) => $q->whereKey($actor->branch_id ?? 0))
                ->orderBy('name')
                ->pluck('name', 'id'),
            'roles' => Role::query()
                ->unless($actor->isSuperAdmin(), fn ($q) => $q->where('name', '!=', User::SUPER_ADMIN_ROLE))
                ->orderBy('name')
                ->pluck('name'),
        ];
    }
}
