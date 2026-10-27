<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use Auditable, BelongsToBranch, SoftDeletes;

    public const STATUSES = ['active' => 'Active', 'inactive' => 'Inactive', 'resigned' => 'Resigned'];

    protected $fillable = [
        'branch_id', 'user_id', 'employee_code', 'name', 'phone', 'email', 'designation',
        'joining_date', 'monthly_salary', 'status', 'address', 'emergency_contact', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'joining_date' => 'date',
            'monthly_salary' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function targets(): HasMany
    {
        return $this->hasMany(StaffTarget::class);
    }

    public function incentives(): HasMany
    {
        return $this->hasMany(Incentive::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when($term, fn ($q) => $q->where(fn ($q) => $q
            ->where('name', 'like', "%{$term}%")
            ->orWhere('employee_code', 'like', "%{$term}%")
            ->orWhere('phone', 'like', "%{$term}%")
            ->orWhere('designation', 'like', "%{$term}%")));
    }
}
