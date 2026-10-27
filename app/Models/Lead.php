<?php

namespace App\Models;

use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use Auditable, BelongsToBranch, SoftDeletes;

    protected $fillable = [
        'branch_id', 'name', 'phone', 'email', 'company', 'address', 'source', 'status',
        'category_id', 'requirement', 'expected_value', 'assigned_to', 'lost_reason', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'source' => LeadSource::class,
            'status' => LeadStatus::class,
            'expected_value' => 'decimal:2',
            'converted_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class)->withTrashed();
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function followUps(): HasMany
    {
        return $this->hasMany(LeadFollowUp::class);
    }

    /** The earliest pending follow-up, for list screens. */
    public function nextFollowUp(): HasOne
    {
        return $this->hasOne(LeadFollowUp::class)->ofMany(
            ['scheduled_at' => 'min', 'id' => 'min'],
            fn ($q) => $q->where('status', LeadFollowUp::PENDING),
        );
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when($term, fn ($q) => $q->where(fn ($q) => $q
            ->where('name', 'like', "%{$term}%")
            ->orWhere('phone', 'like', "%{$term}%")
            ->orWhere('email', 'like', "%{$term}%")
            ->orWhere('company', 'like', "%{$term}%")));
    }
}
