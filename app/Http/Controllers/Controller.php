<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;

abstract class Controller
{
    /**
     * Branch a new record belongs to. Users restricted to their own branch
     * always get it; a submitted branch_id is honoured only for users who
     * may access every branch.
     */
    protected function resolveBranchId(Request $request): int
    {
        $user = $request->user();

        if ($user->canAccessAllBranches() && $request->filled('branch_id')) {
            $branchId = (int) $request->input('branch_id');
            abort_unless(Branch::active()->whereKey($branchId)->exists(), 422, 'Invalid branch.');

            return $branchId;
        }

        abort_if($user->branch_id === null, 403, 'Your account is not assigned to a branch.');

        return $user->branch_id;
    }

    /** Branch filter for list/report screens: own branch, or an optional choice for all-branch users. */
    protected function branchFilter(Request $request): ?int
    {
        $user = $request->user();

        if ($user->canAccessAllBranches()) {
            return $request->integer('branch_id') ?: null;
        }

        // 0 matches nothing, so a user without a branch sees no branch data.
        return $user->branch_id ?? 0;
    }
}
