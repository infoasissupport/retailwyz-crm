<?php

namespace App\Policies;

use App\Models\Incentive;
use App\Models\User;

class IncentivePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('staff.view');
    }

    public function create(User $user): bool
    {
        return $user->can('incentives.manage');
    }

    /**
     * Separation of duties: nobody approves or pays an incentive they raised
     * or one paid to themselves (Super Admin bypasses via Gate::before).
     */
    public function approve(User $user, Incentive $incentive): bool
    {
        return $user->can('incentives.approve')
            && $user->canAccessBranch($incentive->branch_id)
            && $incentive->created_by !== $user->id
            && $incentive->employee?->user_id !== $user->id;
    }

    public function pay(User $user, Incentive $incentive): bool
    {
        return $this->approve($user, $incentive) && $user->can('expenses.manage');
    }
}
