<?php

namespace App\Models;

use App\Enums\AdjustmentStatus;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockAdjustment extends Model
{
    use Auditable, BelongsToBranch;

    public const TYPE_IN = 'in';

    public const TYPE_OUT = 'out';

    public const TYPE_CORRECTION = 'correction';

    protected $fillable = ['branch_id', 'type', 'adjustment_date', 'reason', 'notes'];

    protected function casts(): array
    {
        return [
            'adjustment_date' => 'date',
            'status' => AdjustmentStatus::class,
            'approved_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockAdjustmentItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
