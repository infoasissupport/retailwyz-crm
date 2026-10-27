<?php

namespace App\Models;

use App\Enums\DeliveryStatus;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToBranch;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Status and timestamps are changed only through App\Services\DeliveryService. */
class Delivery extends Model
{
    use Auditable, BelongsToBranch;

    protected $fillable = [
        'customer_name', 'phone', 'address', 'scheduled_date', 'time_slot',
        'employee_id', 'driver_name', 'driver_phone', 'vehicle_no',
        'order_value', 'delivery_charge', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
            'status' => DeliveryStatus::class,
            'order_value' => 'decimal:2',
            'delivery_charge' => 'decimal:2',
            'dispatched_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id')->withTrashed();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function totalValue(): string
    {
        return Money::round(Money::add($this->order_value, $this->delivery_charge));
    }

    public function isOverdue(): bool
    {
        return in_array($this->status, [DeliveryStatus::Pending, DeliveryStatus::Failed], true)
            && $this->scheduled_date->lt(today());
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when($term, fn ($q) => $q->where(fn ($q) => $q
            ->where('delivery_no', 'like', "%{$term}%")
            ->orWhere('customer_name', 'like', "%{$term}%")
            ->orWhere('phone', 'like', "%{$term}%")
            ->orWhere('vehicle_no', 'like', "%{$term}%")));
    }
}
