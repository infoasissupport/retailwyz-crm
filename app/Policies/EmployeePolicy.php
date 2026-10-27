<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;

class EmployeePolicy extends BranchScopedPolicy
{
    protected string $module = 'staff';

    public function create(User $user): bool
    {
        return $user->can('staff.manage');
    }

    public function update(User $user, Employee $employee): bool
    {
        return $this->allows($user, 'manage', $employee);
    }

    public function delete(User $user, Employee $employee): bool
    {
        return $this->allows($user, 'manage', $employee);
    }
}
