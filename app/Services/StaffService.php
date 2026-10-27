<?php

namespace App\Services;

use App\Enums\IncentiveStatus;
use App\Enums\SaleStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\ExpenseCategory;
use App\Models\Incentive;
use App\Models\StaffTarget;
use App\Models\User;
use App\Support\Money;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Attendance, sales targets and incentives.
 *
 * Target achievement = completed sales (net of returns) created by the
 * employee's linked user account, in the target's branch and month.
 */
class StaffService
{
    public const INCENTIVE_EXPENSE_CATEGORY = 'Staff incentives';

    public function __construct(private ExpenseService $expenses) {}

    /**
     * Save a day's attendance sheet. Rows with no status are skipped, so a
     * partially filled sheet never wipes existing marks.
     *
     * @param  array<int, array{status?: ?string, check_in?: ?string, check_out?: ?string, notes?: ?string}>  $rows  employee_id => row
     */
    public function saveAttendance(int $branchId, CarbonInterface $date, array $rows, User $user): int
    {
        if ($date->isFuture()) {
            throw new BusinessRuleException('Attendance cannot be marked for a future date.');
        }

        return DB::transaction(function () use ($branchId, $date, $rows, $user) {
            $rows = array_filter($rows, fn ($row) => ! empty($row['status']));
            $valid = Employee::where('branch_id', $branchId)->whereIn('id', array_keys($rows))->pluck('id')->all();

            if (count($valid) !== count($rows)) {
                throw new BusinessRuleException('Attendance can only be marked for employees of this branch.');
            }

            foreach ($rows as $employeeId => $row) {
                if (! empty($row['check_in']) && ! empty($row['check_out']) && $row['check_out'] <= $row['check_in']) {
                    throw new BusinessRuleException('Check-out time must be after check-in time.');
                }

                // whereDate keeps the lookup driver-independent (SQLite stores the cast as a datetime string).
                $attendance = Attendance::where('employee_id', $employeeId)->whereDate('date', $date)->lockForUpdate()->first()
                    ?? new Attendance(['employee_id' => $employeeId, 'date' => $date->toDateString()]);

                $attendance->fill([
                    'branch_id' => $branchId,
                    'status' => $row['status'],
                    'check_in' => ($row['check_in'] ?? null) ?: null,
                    'check_out' => ($row['check_out'] ?? null) ?: null,
                    'notes' => ($row['notes'] ?? null) ?: null,
                    'marked_by' => $user->id,
                ])->save();
            }

            return count($rows);
        });
    }

    /**
     * Achieved sales per target, in one query per call.
     *
     * @param  Collection<int, StaffTarget>  $targets  with employee loaded
     * @return array<int, string> target id => achieved amount
     */
    public function achievements(Collection $targets): array
    {
        $result = [];

        foreach ($targets->groupBy(fn ($t) => $t->period->format('Y-m').'|'.$t->branch_id) as $group) {
            $first = $group->first();
            $userIds = $group->pluck('employee.user_id')->filter()->unique()->all();

            $totals = $userIds === [] ? collect() : DB::table('sales')
                ->where('branch_id', $first->branch_id)
                ->where('status', SaleStatus::Completed->value)
                ->whereIn('created_by', $userIds)
                ->whereBetween('sale_date', [$first->period->copy()->startOfMonth()->toDateString(), $first->period->copy()->endOfMonth()->toDateString().' 23:59:59'])
                ->selectRaw('created_by, COALESCE(SUM(grand_total - returned_amount), 0) as achieved')
                ->groupBy('created_by')
                ->pluck('achieved', 'created_by');

            foreach ($group as $target) {
                $result[$target->id] = Money::round($totals[$target->employee->user_id] ?? '0');
            }
        }

        return $result;
    }

    /** Incentive earned for an achieved amount; zero unless the target is met. */
    public function earnedIncentive(StaffTarget $target, string $achieved): string
    {
        if (Money::cmp($achieved, $target->target_amount) < 0 || Money::cmp($target->target_amount, '0') <= 0) {
            return '0.00';
        }

        return $target->incentive_type === StaffTarget::FIXED
            ? Money::round($target->incentive_value)
            : Money::round(Money::percent($achieved, $target->incentive_value));
    }

    /**
     * Create pending target-bonus incentives for met targets that have none yet.
     * Safe to run repeatedly: each target can hold at most one incentive.
     *
     * @param  Collection<int, StaffTarget>  $targets  with employee and incentive loaded
     */
    public function generateTargetIncentives(Collection $targets, User $user): int
    {
        $achieved = $this->achievements($targets);
        $created = 0;

        DB::transaction(function () use ($targets, $achieved, $user, &$created) {
            foreach ($targets as $target) {
                $amount = $this->earnedIncentive($target, $achieved[$target->id]);

                if ($target->incentive || Money::cmp($amount, '0') <= 0) {
                    continue;
                }

                $incentive = new Incentive([
                    'type' => 'target_bonus',
                    'amount' => $amount,
                    'description' => 'Target met for '.$target->period->format('F Y').': '.$achieved[$target->id].' of '.$target->target_amount,
                ]);
                $incentive->forceFill([
                    'employee_id' => $target->employee_id,
                    'branch_id' => $target->branch_id,
                    'staff_target_id' => $target->id,
                    'period' => $target->period,
                    'status' => IncentiveStatus::Pending,
                    'created_by' => $user->id,
                ])->save();
                $created++;
            }
        });

        return $created;
    }

    public function createIncentive(Employee $employee, array $data, User $user): Incentive
    {
        $incentive = new Incentive($data);
        $incentive->forceFill([
            'employee_id' => $employee->id,
            'branch_id' => $employee->branch_id,
            'period' => \Carbon\Carbon::parse($data['period'])->startOfMonth(),
            'amount' => Money::round($data['amount']),
            'status' => IncentiveStatus::Pending,
            'created_by' => $user->id,
        ])->save();

        return $incentive;
    }

    public function approve(Incentive $incentive, User $user, bool $approve = true): Incentive
    {
        return DB::transaction(function () use ($incentive, $user, $approve) {
            $incentive = $this->lockInStatus($incentive, IncentiveStatus::Pending);

            $incentive->forceFill([
                'status' => $approve ? IncentiveStatus::Approved : IncentiveStatus::Rejected,
                'approved_by' => $user->id,
                'approved_at' => now(),
            ])->save();

            return $incentive;
        });
    }

    /**
     * Pay an approved incentive. The payout is booked as an expense so it
     * appears in the finance ledger and profit & loss.
     *
     * @param  array{payment_method: string, paid_on: string}  $data
     */
    public function pay(Incentive $incentive, array $data, User $user): Incentive
    {
        return DB::transaction(function () use ($incentive, $data, $user) {
            $incentive = $this->lockInStatus($incentive, IncentiveStatus::Approved);
            $employee = $incentive->employee()->firstOrFail();

            $expense = $this->expenses->create([
                'branch_id' => $incentive->branch_id,
                'expense_category_id' => ExpenseCategory::firstOrCreate(['name' => self::INCENTIVE_EXPENSE_CATEGORY])->id,
                'expense_date' => $data['paid_on'],
                'amount' => $incentive->amount,
                'payment_method' => $data['payment_method'],
                'paid_to' => "{$employee->name} ({$employee->employee_code})",
                'description' => "{$incentive->typeLabel()} incentive for {$incentive->period->format('F Y')}",
            ], $user);

            $incentive->forceFill([
                'status' => IncentiveStatus::Paid,
                'expense_id' => $expense->id,
                'paid_at' => now(),
            ])->save();

            return $incentive;
        });
    }

    private function lockInStatus(Incentive $incentive, IncentiveStatus $expected): Incentive
    {
        $incentive = Incentive::whereKey($incentive->id)->lockForUpdate()->firstOrFail();

        if ($incentive->status !== $expected) {
            throw new BusinessRuleException("This incentive is {$incentive->status->value}, not {$expected->value}.");
        }

        return $incentive;
    }
}
