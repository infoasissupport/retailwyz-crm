<?php

namespace App\Models\Concerns;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * For records owned by a branch. Use ->visibleTo($user) in queries; policies
 * use $user->canAccessBranch($model->branch_id) for single records.
 */
trait BelongsToBranch
{
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->canAccessAllBranches()) {
            return $query;
        }

        return $query->where($this->qualifyColumn('branch_id'), $user->branch_id);
    }
}
