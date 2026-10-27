<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Money ledger row. Posted only by App\Services\FinanceService.
 */
class FinancialTransaction extends Model
{
    use BelongsToBranch;

    public const TYPE_SALE_PAYMENT = 'sale_payment';

    public const TYPE_SALE_REFUND = 'sale_refund';

    public const TYPE_PURCHASE_PAYMENT = 'purchase_payment';

    public const TYPE_PURCHASE_REFUND = 'purchase_refund';

    public const TYPE_EXPENSE = 'expense';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'amount' => 'decimal:2',
            'method' => PaymentMethod::class,
        ];
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function cashRegister(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
