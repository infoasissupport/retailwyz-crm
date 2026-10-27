<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Models\Expense;
use App\Models\User;
use App\Support\Money;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Expenses and their ledger rows. Attachments live on the private "local"
 * disk; files are written after the DB work succeeds and removed on rollback.
 */
class ExpenseService
{
    private const DISK = 'local';

    public function __construct(
        private DocumentNumberService $numbers,
        private FinanceService $finance,
    ) {}

    /** @param  array<string, mixed>  $data  validated fields incl. branch_id */
    public function create(array $data, User $user, ?UploadedFile $attachment = null): Expense
    {
        $path = $attachment?->store('expenses', self::DISK);

        try {
            return DB::transaction(function () use ($data, $user, $path) {
                $expense = new Expense;
                $expense->fill($this->normalize($data));
                $expense->forceFill([
                    'reference_no' => $this->numbers->next('expense', 'expense_prefix'),
                    'attachment_path' => $path,
                    'cash_register_id' => $this->registerFor($expense, $user),
                    'created_by' => $user->id,
                ])->save();

                $this->finance->syncExpense($expense);

                return $expense;
            });
        } catch (\Throwable $e) {
            $path && Storage::disk(self::DISK)->delete($path);
            throw $e;
        }
    }

    public function update(Expense $expense, array $data, User $user, ?UploadedFile $attachment = null): Expense
    {
        $newPath = $attachment?->store('expenses', self::DISK);
        $oldPath = $expense->attachment_path;

        try {
            DB::transaction(function () use ($expense, $data, $user, $newPath) {
                $expense->fill($this->normalize($data));
                if ($newPath) {
                    $expense->forceFill(['attachment_path' => $newPath]);
                }
                if ($expense->isDirty('payment_method') || $expense->isDirty('branch_id')) {
                    $expense->forceFill(['cash_register_id' => $this->registerFor($expense, $user)]);
                }
                $expense->save();

                $this->finance->syncExpense($expense);
            });
        } catch (\Throwable $e) {
            $newPath && Storage::disk(self::DISK)->delete($newPath);
            throw $e;
        }

        if ($newPath && $oldPath) {
            Storage::disk(self::DISK)->delete($oldPath);
        }

        return $expense;
    }

    public function delete(Expense $expense): void
    {
        DB::transaction(function () use ($expense) {
            $this->finance->removeExpense($expense);
            $expense->delete();
        });

        if ($expense->attachment_path) {
            Storage::disk(self::DISK)->delete($expense->attachment_path);
        }
    }

    private function normalize(array $data): array
    {
        $data['amount'] = Money::round($data['amount']);
        $data['tax_amount'] = Money::round($data['tax_amount'] ?? '0');

        return $data;
    }

    private function registerFor(Expense $expense, User $user): ?int
    {
        $method = $expense->payment_method instanceof PaymentMethod ? $expense->payment_method : PaymentMethod::from($expense->payment_method);

        return $method === PaymentMethod::Cash
            ? $this->finance->openRegisterFor($user, $expense->branch_id)?->id
            : null;
    }
}
