<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PurchaseStatus;
use App\Enums\SaleStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\User;
use App\Support\Money;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Records payments and refunds against sales (receivables) and purchases
 * (payables) and keeps paid_amount / payment_status consistent.
 */
class PaymentService
{
    public function __construct(
        private DocumentNumberService $numbers,
        private FinanceService $finance,
    ) {}

    /**
     * @param  array{amount: string, method: string, paid_on?: string, reference?: ?string, notes?: ?string, idempotency_key?: ?string}  $data
     * @param  bool  $refund  true = money goes back the other way (customer refund / supplier refund)
     */
    public function record(Sale|Purchase $payable, array $data, User $user, bool $refund = false): Payment
    {
        if (! empty($data['idempotency_key']) && $existing = Payment::where('idempotency_key', $data['idempotency_key'])->first()) {
            return $existing;
        }

        try {
            return DB::transaction(fn () => $this->recordLocked($payable, $data, $user, $refund));
        } catch (UniqueConstraintViolationException $e) {
            // A concurrent retry with the same key won the race.
            return Payment::where('idempotency_key', $data['idempotency_key'] ?? null)->first() ?? throw $e;
        }
    }

    /** Recalculate payment_status from the stored totals. */
    public function refreshStatus(Sale|Purchase $payable): void
    {
        $due = Money::sub($payable->grand_total, $payable->returned_amount);
        $paid = $payable->paid_amount;

        $status = match (true) {
            Money::cmp($paid, $due) > 0 => PaymentStatus::Overpaid,
            Money::cmp($paid, $due) === 0 => PaymentStatus::Paid,
            Money::cmp($paid, '0') > 0 => PaymentStatus::Partial,
            default => PaymentStatus::Unpaid,
        };

        $payable->forceFill(['payment_status' => $status])->save();
    }

    private function recordLocked(Sale|Purchase $payable, array $data, User $user, bool $refund): Payment
    {
        $payable = $payable::query()->whereKey($payable->getKey())->lockForUpdate()->firstOrFail();
        $isSale = $payable instanceof Sale;

        if (($isSale && $payable->status === SaleStatus::Cancelled)
            || (! $isSale && in_array($payable->status, [PurchaseStatus::Cancelled, PurchaseStatus::Draft], true))) {
            throw new BusinessRuleException('Payments cannot be recorded against a '.$payable->status->value.' document.');
        }

        $amount = Money::round($data['amount']);
        if (Money::cmp($amount, '0') <= 0) {
            throw new BusinessRuleException('Payment amount must be greater than zero.');
        }

        $balance = $payable->balanceDue();
        if (! $refund && Money::cmp($amount, $balance) > 0) {
            throw new BusinessRuleException("Payment of {$amount} exceeds the outstanding balance of {$balance}.");
        }
        if ($refund && Money::cmp($amount, Money::mul($balance, '-1')) > 0) {
            throw new BusinessRuleException('Refund exceeds the credit available on this document.');
        }

        // Sales receive money in; purchases pay money out. Refunds reverse that.
        $direction = ($isSale xor $refund) ? Payment::IN : Payment::OUT;
        $method = PaymentMethod::from($data['method']);

        $register = $method === PaymentMethod::Cash
            ? $this->finance->openRegisterFor($user, $payable->branch_id)
            : null;

        $payment = new Payment;
        $payment->forceFill([
            'branch_id' => $payable->branch_id,
            'payment_no' => $this->numbers->next('payment', 'payment_prefix'),
            'direction' => $direction,
            'payable_type' => $payable->getMorphClass(),
            'payable_id' => $payable->getKey(),
            'customer_id' => $isSale ? $payable->customer_id : null,
            'supplier_id' => $isSale ? null : $payable->supplier_id,
            'amount' => $amount,
            'method' => $method,
            'reference' => $data['reference'] ?? null,
            'paid_on' => $data['paid_on'] ?? now()->toDateString(),
            'cash_register_id' => $register?->id,
            'idempotency_key' => $data['idempotency_key'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_by' => $user->id,
        ])->save();

        $this->finance->postPayment($payment);

        $payable->forceFill([
            'paid_amount' => Money::round($refund
                ? Money::sub($payable->paid_amount, $amount)
                : Money::add($payable->paid_amount, $amount)),
        ]);
        $this->refreshStatus($payable);

        return $payment;
    }
}
