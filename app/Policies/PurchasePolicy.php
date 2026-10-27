<?php

namespace App\Policies;

use App\Models\Purchase;
use App\Models\User;

class PurchasePolicy extends BranchScopedPolicy
{
    protected string $module = 'purchases';

    public function create(User $user): bool
    {
        return $user->can('purchases.create');
    }

    public function update(User $user, Purchase $purchase): bool
    {
        return $this->allows($user, 'update', $purchase) && $purchase->status->isEditable();
    }

    public function receive(User $user, Purchase $purchase): bool
    {
        return $this->allows($user, 'receive', $purchase);
    }

    public function cancel(User $user, Purchase $purchase): bool
    {
        return $this->allows($user, 'cancel', $purchase);
    }

    public function return(User $user, Purchase $purchase): bool
    {
        return $this->allows($user, 'return', $purchase);
    }

    public function recordPayment(User $user, Purchase $purchase): bool
    {
        return $user->can('payments.create') && $user->canAccessBranch($purchase->branch_id);
    }

    public function refund(User $user, Purchase $purchase): bool
    {
        return $user->can('payments.refund') && $user->canAccessBranch($purchase->branch_id);
    }
}
