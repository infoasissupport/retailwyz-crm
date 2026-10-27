<?php

namespace App\Policies;

use App\Models\LeadFollowUp;
use App\Models\User;

class LeadFollowUpPolicy extends BranchScopedPolicy
{
    protected string $module = 'leads';

    public function update(User $user, LeadFollowUp $followUp): bool
    {
        return $this->allows($user, 'manage', $followUp);
    }
}
