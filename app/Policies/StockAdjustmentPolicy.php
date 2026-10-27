<?php

namespace App\Policies;

use App\Models\StockAdjustment;
use App\Models\User;

class StockAdjustmentPolicy extends BranchScopedPolicy
{
    protected string $module = 'inventory';

    public function create(User $user): bool
    {
        return $user->can('inventory.adjust');
    }

    /** Approval is a separate permission, and nobody approves their own adjustment unless they are Super Admin (via Gate::before). */
    public function approve(User $user, StockAdjustment $adjustment): bool
    {
        return $this->allows($user, 'approve', $adjustment) && $adjustment->created_by !== $user->id;
    }
}
