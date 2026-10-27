<?php

namespace App\Policies;

use App\Models\Sale;
use App\Models\User;

class SalePolicy extends BranchScopedPolicy
{
    protected string $module = 'sales';

    public function create(User $user): bool
    {
        return $user->can('sales.create');
    }

    public function cancel(User $user, Sale $sale): bool
    {
        return $this->allows($user, 'cancel', $sale);
    }

    public function return(User $user, Sale $sale): bool
    {
        return $this->allows($user, 'return', $sale);
    }

    public function recordPayment(User $user, Sale $sale): bool
    {
        return $user->can('payments.create') && $user->canAccessBranch($sale->branch_id);
    }

    public function refund(User $user, Sale $sale): bool
    {
        return $user->can('payments.refund') && $user->canAccessBranch($sale->branch_id);
    }
}
