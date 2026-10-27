<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Shared checks for records that belong to a branch: the user needs the
 * module permission and access to the record's branch.
 */
abstract class BranchScopedPolicy
{
    /** Permission prefix, e.g. "sales". */
    protected string $module;

    public function viewAny(User $user): bool
    {
        return $user->can("{$this->module}.view");
    }

    public function view(User $user, Model $model): bool
    {
        return $this->allows($user, 'view', $model);
    }

    protected function allows(User $user, string $ability, Model $model): bool
    {
        return $user->can("{$this->module}.{$ability}") && $user->canAccessBranch($model->branch_id);
    }
}
