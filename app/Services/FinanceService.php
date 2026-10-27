<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Exceptions\BusinessRuleException;
use App\Models\CashRegister;
use App\Models\Expense;
use App\Models\FinancialTransaction;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Facades\DB;

/**
 * Posts money movements to the financial_transactions ledger and manages
 * cash register sessions.
 */
class FinanceService
{
    public function postPayment(Payment $payment): FinancialTransaction
    {
        $isPurchase = $payment->payable_type === (new Purchase)->getMorphClass();
        $type = match (true) {
            $isPurchase && $payment->direction === Payment::OUT => FinancialTransaction::TYPE_PURCHASE_PAYMENT,
            $isPurchase => FinancialTransaction::TYPE_PURCHASE_REFUND,
            $payment->direction === Payment::IN => FinancialTransaction::TYPE_SALE_PAYMENT,
            default => FinancialTransaction::TYPE_SALE_REFUND,
        };

        return $this->post([
            'branch_id' => $payment->branch_id,
            'transaction_date' => $payment->paid_on,
            'type' => $type,
            'direction' => $payment->direction,
            'amount' => $payment->amount,
            'method' => $payment->method,
            'source_type' => $payment->getMorphClass(),
            'source_id' => $payment->getKey(),
            'cash_register_id' => $payment->cash_register_id,
            'description' => $payment->payment_no,
        ]);
    }

    /** Create or refresh the ledger row for an expense. */
    public function syncExpense(Expense $expense): FinancialTransaction
    {
        $attributes = [
            'branch_id' => $expense->branch_id,
            'transaction_date' => $expense->expense_date,
            'type' => FinancialTransaction::TYPE_EXPENSE,
            'direction' => Payment::OUT,
            'amount' => $expense->amount,
            'method' => $expense->payment_method,
            'cash_register_id' => $expense->cash_register_id,
            'description' => $expense->reference_no,
        ];

        $existing = $expense->transaction()->first();
        if ($existing) {
            $existing->forceFill($this->withAccount($attributes))->save();

            return $existing;
        }

        return $this->post($attributes + [
            'source_type' => $expense->getMorphClass(),
            'source_id' => $expense->getKey(),
        ]);
    }

    public function removeExpense(Expense $expense): void
    {
        $expense->transaction()->delete();
    }

    public function openRegisterFor(User $user, int $branchId): ?CashRegister
    {
        return CashRegister::query()
            ->where('user_id', $user->id)
            ->where('branch_id', $branchId)
            ->where('status', CashRegister::OPEN)
            ->latest('opened_at')
            ->first();
    }

    public function openRegister(User $user, int $branchId, string $openingBalance, ?string $notes = null): CashRegister
    {
        return DB::transaction(function () use ($user, $branchId, $openingBalance, $notes) {
            if ($this->openRegisterFor($user, $branchId)) {
                throw new BusinessRuleException('You already have an open cash register for this branch.');
            }

            $register = new CashRegister;
            $register->forceFill([
                'branch_id' => $branchId,
                'user_id' => $user->id,
                'opened_at' => now(),
                'opening_balance' => Money::round($openingBalance),
                'status' => CashRegister::OPEN,
                'notes' => $notes,
            ])->save();

            return $register;
        });
    }

    public function expectedCash(CashRegister $register): string
    {
        $totals = $register->transactions()
            ->where('account', 'cash')
            ->selectRaw('direction, SUM(amount) as total')
            ->groupBy('direction')
            ->pluck('total', 'direction');

        return Money::round(Money::sub(
            Money::add($register->opening_balance, $totals[Payment::IN] ?? '0'),
            $totals[Payment::OUT] ?? '0',
        ));
    }

    public function closeRegister(CashRegister $register, string $countedCash, ?string $notes = null): CashRegister
    {
        return DB::transaction(function () use ($register, $countedCash, $notes) {
            $register = CashRegister::whereKey($register->id)->lockForUpdate()->firstOrFail();

            if (! $register->isOpen()) {
                throw new BusinessRuleException('This cash register is already closed.');
            }

            $register->forceFill([
                'closed_at' => now(),
                'expected_balance' => $this->expectedCash($register),
                'closing_balance' => Money::round($countedCash),
                'status' => CashRegister::CLOSED,
                'notes' => trim(($register->notes ? $register->notes."\n" : '').($notes ?? '')) ?: null,
            ])->save();

            return $register;
        });
    }

    private function post(array $attributes): FinancialTransaction
    {
        $transaction = new FinancialTransaction;
        $transaction->forceFill($this->withAccount($attributes) + ['created_by' => auth()->id()])->save();

        return $transaction;
    }

    private function withAccount(array $attributes): array
    {
        $method = $attributes['method'] instanceof PaymentMethod
            ? $attributes['method']
            : PaymentMethod::from($attributes['method']);

        return ['account' => $method->account()] + $attributes;
    }
}
