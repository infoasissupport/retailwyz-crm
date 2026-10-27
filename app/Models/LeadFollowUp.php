<?php

namespace App\Models;

use App\Enums\FollowUpType;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadFollowUp extends Model
{
    use Auditable, BelongsToBranch;

    public const PENDING = 'pending';

    public const DONE = 'done';

    public const CANCELLED = 'cancelled';

    protected $fillable = ['type', 'scheduled_at', 'notes', 'assigned_to'];

    protected function casts(): array
    {
        return [
            'type' => FollowUpType::class,
            'scheduled_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class)->withTrashed();
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::PENDING;
    }

    public function isOverdue(): bool
    {
        return $this->isPending() && $this->scheduled_at->isPast();
    }
}
