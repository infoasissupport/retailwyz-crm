<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Enums\PurchaseStatus;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToBranch;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Totals, status and payment fields are calculated by App\Services\PurchaseService
 * and are deliberately not mass assignable.
 */
class Purchase extends Model
{
    use Auditable, BelongsToBranch;

    protected $fillable = [
        'branch_id', 'supplier_id', 'supplier_invoice_no', 'purchase_date',
        'expected_date', 'shipping_cost', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'expected_date' => 'date',
            'received_at' => 'datetime',
            'status' => PurchaseStatus::class,
            'payment_status' => PaymentStatus::class,
            'prices_include_tax' => 'boolean',
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'shipping_cost' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'returned_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class)->withTrashed();
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(PurchaseReturn::class);
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Amount still owed to the supplier (negative = supplier owes us). */
    public function balanceDue(): string
    {
        return Money::round(Money::sub(Money::sub($this->grand_total, $this->returned_amount), $this->paid_amount));
    }
}
