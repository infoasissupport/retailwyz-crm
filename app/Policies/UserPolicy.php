<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('users.view');
    }

    public function view(User $user, User $model): bool
    {
        return $user->can('users.view') && $user->canAccessBranch($model->branch_id);
    }

    public function create(User $user): bool
    {
        return $user->can('users.manage');
    }

    /** Only a Super Admin (Gate::before) may modify a Super Admin account. */
    public function update(User $user, User $model): bool
    {
        return $user->can('users.manage') && ! $model->isSuperAdmin() && $user->canAccessBranch($model->branch_id);
    }

    public function delete(User $user, User $model): bool
    {
        return $user->id !== $model->id && $this->update($user, $model);
    }
}
