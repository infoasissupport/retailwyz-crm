<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Enums\SaleStatus;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToBranch;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Totals, status and payment fields are calculated by App\Services\SaleService
 * and are deliberately not mass assignable.
 */
class Sale extends Model
{
    use Auditable, BelongsToBranch;

    protected $fillable = ['branch_id', 'customer_id', 'sale_date', 'notes'];

    protected function casts(): array
    {
        return [
            'sale_date' => 'date',
            'cancelled_at' => 'datetime',
            'status' => SaleStatus::class,
            'payment_status' => PaymentStatus::class,
            'prices_include_tax' => 'boolean',
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'returned_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(SaleReturn::class);
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Amount the customer still owes (negative = refund owed to customer). */
    public function balanceDue(): string
    {
        return Money::round(Money::sub(Money::sub($this->grand_total, $this->returned_amount), $this->paid_amount));
    }
}
