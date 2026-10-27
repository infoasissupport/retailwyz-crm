<?php

namespace App\Policies;

use App\Models\CashRegister;
use App\Models\User;

class CashRegisterPolicy
{
    public function open(User $user): bool
    {
        return $user->can('cash-register.manage');
    }

    /** Cashiers close their own register; finance staff may close any in their branch. */
    public function close(User $user, CashRegister $register): bool
    {
        if (! $user->canAccessBranch($register->branch_id)) {
            return false;
        }

        return ($register->user_id === $user->id && $user->can('cash-register.manage'))
            || $user->can('finance.view');
    }
}
