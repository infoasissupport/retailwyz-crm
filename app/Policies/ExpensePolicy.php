<?php

namespace App\Policies;

use App\Models\Expense;
use App\Models\User;

class ExpensePolicy extends BranchScopedPolicy
{
    protected string $module = 'expenses';

    public function create(User $user): bool
    {
        return $user->can('expenses.manage');
    }

    public function update(User $user, Expense $expense): bool
    {
        return $this->allows($user, 'manage', $expense);
    }

    public function delete(User $user, Expense $expense): bool
    {
        return $this->allows($user, 'manage', $expense);
    }
}
