<?php

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;

class LeadPolicy extends BranchScopedPolicy
{
    protected string $module = 'leads';

    public function create(User $user): bool
    {
        return $user->can('leads.manage');
    }

    public function update(User $user, Lead $lead): bool
    {
        return $this->allows($user, 'manage', $lead);
    }

    public function delete(User $user, Lead $lead): bool
    {
        return $this->allows($user, 'manage', $lead) && $lead->customer_id === null;
    }

    public function convert(User $user, Lead $lead): bool
    {
        return $this->allows($user, 'manage', $lead) && $user->can('customers.manage');
    }
}
