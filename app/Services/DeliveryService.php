<?php

namespace App\Services;

use App\Enums\DeliveryStatus;
use App\Enums\SaleStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Delivery;
use App\Models\Employee;
use App\Models\Sale;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Facades\DB;

/**
 * Delivery scheduling and status flow:
 *
 *   pending -> out_for_delivery -> delivered
 *        \            \-> failed -> (reschedule) pending
 *         \-> cancelled
 *
 * Deliveries never move stock: the sale already deducted it. Every status
 * change locks the row and checks the current state, so a double click or a
 * retried request cannot apply a transition twice.
 */
class DeliveryService
{
    public function __construct(private DocumentNumberService $numbers) {}

    public function create(array $data, int $branchId, User $user): Delivery
    {
        return DB::transaction(function () use ($data, $branchId, $user) {
            $sale = $this->linkedSale($data['sale_id'] ?? null, $branchId);

            $delivery = new Delivery;
            $delivery->fill($this->withDriver($data, $branchId));
            $delivery->forceFill([
                'branch_id' => $branchId,
                'delivery_no' => $this->numbers->next('delivery', 'delivery_prefix'),
                'sale_id' => $sale?->id,
                'customer_id' => $sale?->customer_id ?? ($data['customer_id'] ?? null),
                'order_value' => $sale ? Money::round(Money::sub($sale->grand_total, $sale->returned_amount)) : Money::round($data['order_value'] ?? '0'),
                'delivery_charge' => Money::round($data['delivery_charge'] ?? '0'),
                'status' => DeliveryStatus::Pending,
                'created_by' => $user->id,
            ])->save();

            return $delivery;
        });
    }

    /** Edit details while pending or failed; a failed delivery returns to pending (rescheduled). */
    public function update(Delivery $delivery, array $data): Delivery
    {
        return DB::transaction(function () use ($delivery, $data) {
            $delivery = $this->lock($delivery);

            if (! $delivery->status->isEditable()) {
                throw new BusinessRuleException("A delivery that is {$delivery->status->label()} can no longer be edited.");
            }

            unset($data['sale_id'], $data['customer_id']);
            if ($delivery->sale_id) {
                unset($data['order_value']); // value follows the invoice
            }

            $delivery->fill($this->withDriver($data, $delivery->branch_id));
            if ($delivery->status === DeliveryStatus::Failed) {
                $delivery->forceFill(['status' => DeliveryStatus::Pending, 'failure_reason' => null]);
            }
            $delivery->save();

            return $delivery;
        });
    }

    public function start(Delivery $delivery): Delivery
    {
        return $this->transition($delivery, [DeliveryStatus::Pending], function (Delivery $d) {
            if (! $d->driver_name) {
                throw new BusinessRuleException('Assign a driver before starting the delivery.');
            }

            $d->forceFill([
                'status' => DeliveryStatus::OutForDelivery,
                'dispatched_at' => now(),
                'attempts' => $d->attempts + 1,
            ]);
        });
    }

    public function complete(Delivery $delivery, ?string $receivedBy, ?string $notes = null): Delivery
    {
        return $this->transition($delivery, [DeliveryStatus::OutForDelivery], fn (Delivery $d) => $d->forceFill([
            'status' => DeliveryStatus::Delivered,
            'delivered_at' => now(),
            'received_by' => $receivedBy,
            'notes' => $notes ? trim(($d->notes ? $d->notes."\n" : '').$notes) : $d->notes,
        ]));
    }

    public function fail(Delivery $delivery, string $reason): Delivery
    {
        return $this->transition($delivery, [DeliveryStatus::OutForDelivery], fn (Delivery $d) => $d->forceFill([
            'status' => DeliveryStatus::Failed,
            'failure_reason' => $reason,
        ]));
    }

    public function cancel(Delivery $delivery, string $reason): Delivery
    {
        return $this->transition($delivery, [DeliveryStatus::Pending, DeliveryStatus::Failed], fn (Delivery $d) => $d->forceFill([
            'status' => DeliveryStatus::Cancelled,
            'failure_reason' => $reason,
        ]));
    }

    /** @param  list<DeliveryStatus>  $from */
    private function transition(Delivery $delivery, array $from, callable $apply): Delivery
    {
        return DB::transaction(function () use ($delivery, $from, $apply) {
            $delivery = $this->lock($delivery);

            if (! in_array($delivery->status, $from, true)) {
                throw new BusinessRuleException("Delivery {$delivery->delivery_no} is already {$delivery->status->label()}.");
            }

            $apply($delivery);
            $delivery->save();

            return $delivery;
        });
    }

    private function lock(Delivery $delivery): Delivery
    {
        return Delivery::whereKey($delivery->id)->lockForUpdate()->firstOrFail();
    }

    private function linkedSale(?int $saleId, int $branchId): ?Sale
    {
        if (! $saleId) {
            return null;
        }

        $sale = Sale::whereKey($saleId)->where('branch_id', $branchId)->first();

        if (! $sale || $sale->status !== SaleStatus::Completed) {
            throw new BusinessRuleException('Deliveries can only be linked to a completed sale of this branch.');
        }

        return $sale;
    }

    /** A driver picked from staff fills in name and phone; it must belong to the same branch. */
    private function withDriver(array $data, int $branchId): array
    {
        if (empty($data['employee_id'])) {
            return $data;
        }

        $driver = Employee::where('branch_id', $branchId)->find($data['employee_id'])
            ?? throw new BusinessRuleException('Choose a driver from this branch’s staff.');

        $data['driver_name'] = $driver->name;
        $data['driver_phone'] = ($data['driver_phone'] ?? null) ?: $driver->phone;

        return $data;
    }
}
