<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/** Monthly sales target for one employee, with the incentive rule if it is met. */
class StaffTarget extends Model
{
    use Auditable, BelongsToBranch;

    public const PERCENT = 'percent';

    public const FIXED = 'fixed';

    protected $fillable = ['target_amount', 'incentive_type', 'incentive_value', 'notes'];

    protected function casts(): array
    {
        return [
            'period' => 'date',
            'target_amount' => 'decimal:2',
            'incentive_value' => 'decimal:2',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }

    public function incentive(): HasOne
    {
        return $this->hasOne(Incentive::class);
    }
}
