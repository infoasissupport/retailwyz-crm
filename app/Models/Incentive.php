<?php

namespace App\Models;

use App\Enums\IncentiveStatus;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Status, approval and payment fields are set only by App\Services\StaffService. */
class Incentive extends Model
{
    use Auditable, BelongsToBranch;

    public const TYPES = [
        'target_bonus' => 'Target bonus',
        'commission' => 'Commission',
        'performance' => 'Performance',
        'other' => 'Other',
    ];

    protected $fillable = ['type', 'amount', 'description'];

    protected function casts(): array
    {
        return [
            'period' => 'date',
            'amount' => 'decimal:2',
            'status' => IncentiveStatus::class,
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(StaffTarget::class, 'staff_target_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? ucfirst($this->type);
    }
}
