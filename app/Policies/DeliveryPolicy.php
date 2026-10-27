<?php

namespace App\Policies;

use App\Models\Delivery;
use App\Models\User;

class DeliveryPolicy extends BranchScopedPolicy
{
    protected string $module = 'deliveries';

    public function create(User $user): bool
    {
        return $user->can('deliveries.manage');
    }

    public function update(User $user, Delivery $delivery): bool
    {
        return $this->allows($user, 'manage', $delivery);
    }
}
