<?php

namespace App\Services;

use App\Enums\AdjustmentStatus;
use App\Enums\StockMovementType;
use App\Exceptions\BusinessRuleException;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Facades\DB;

/**
 * Manual stock-in, stock-out and corrections. Adjustments are created as
 * pending and only change stock once approved by a user holding
 * inventory.approve.
 */
class StockAdjustmentService
{
    public function __construct(
        private InventoryService $inventory,
        private DocumentNumberService $numbers,
    ) {}

    /**
     * Item quantities are entered as positive numbers; the adjustment type
     * decides the sign. Corrections accept signed quantities.
     *
     * @param  array{branch_id: int, type: string, adjustment_date: string, reason: string, notes?: ?string,
     *     items: list<array{product_id: int, product_variant_id?: ?int, quantity: string, notes?: ?string}>}  $data
     */
    public function create(array $data, User $user, bool $approveNow = false): StockAdjustment
    {
        return DB::transaction(function () use ($data, $user, $approveNow) {
            $adjustment = new StockAdjustment;
            $adjustment->fill($data);
            $adjustment->forceFill([
                'reference_no' => $this->numbers->next('adjustment', 'adjustment_prefix'),
                'status' => AdjustmentStatus::Pending,
                'created_by' => $user->id,
            ])->save();

            $products = Product::whereIn('id', array_column($data['items'], 'product_id'))->get()->keyBy('id');

            foreach ($data['items'] as $line) {
                $product = $products->get($line['product_id']) ?? throw new BusinessRuleException('Invalid product on adjustment.');
                $variantId = $line['product_variant_id'] ?? null;
                if ($variantId !== null && ! $product->variants()->whereKey($variantId)->exists()) {
                    throw new BusinessRuleException("Invalid variant selected for {$product->name}.");
                }

                $quantity = $this->signedQuantity($data['type'], $line['quantity']);

                if (Money::isZero($quantity)) {
                    throw new BusinessRuleException('Adjustment quantities cannot be zero.');
                }

                $adjustment->items()->create([
                    'product_id' => $product->id,
                    'product_variant_id' => $variantId,
                    'quantity' => $quantity,
                    'unit_cost' => $product->cost_price,
                    'notes' => $line['notes'] ?? null,
                ]);
            }

            if ($approveNow) {
                $this->approve($adjustment, $user);
            }

            return $adjustment->refresh();
        });
    }

    public function approve(StockAdjustment $adjustment, User $user): StockAdjustment
    {
        return DB::transaction(function () use ($adjustment, $user) {
            $adjustment = $this->lockPending($adjustment);

            foreach ($adjustment->items as $item) {
                $this->inventory->move(
                    $adjustment->branch_id, $item->product_id, $item->product_variant_id,
                    $item->quantity, StockMovementType::Adjustment, $adjustment, $item->unit_cost,
                    "{$adjustment->reference_no}: {$adjustment->reason}",
                );
            }

            $adjustment->forceFill([
                'status' => AdjustmentStatus::Approved,
                'approved_by' => $user->id,
                'approved_at' => now(),
            ])->save();

            return $adjustment;
        });
    }

    public function reject(StockAdjustment $adjustment, User $user): StockAdjustment
    {
        return DB::transaction(function () use ($adjustment, $user) {
            $adjustment = $this->lockPending($adjustment);

            $adjustment->forceFill([
                'status' => AdjustmentStatus::Rejected,
                'approved_by' => $user->id,
                'approved_at' => now(),
            ])->save();

            return $adjustment;
        });
    }

    /** The status check under row lock is what stops a double-approval from moving stock twice. */
    private function lockPending(StockAdjustment $adjustment): StockAdjustment
    {
        $adjustment = StockAdjustment::whereKey($adjustment->id)->lockForUpdate()->firstOrFail();

        if ($adjustment->status !== AdjustmentStatus::Pending) {
            throw new BusinessRuleException("Adjustment {$adjustment->reference_no} is already {$adjustment->status->value}.");
        }

        return $adjustment;
    }

    private function signedQuantity(string $type, string $quantity): string
    {
        return match ($type) {
            StockAdjustment::TYPE_IN => Money::normalize(ltrim($quantity, '-+')),
            StockAdjustment::TYPE_OUT => '-'.ltrim($quantity, '-+'),
            default => Money::normalize($quantity),
        };
    }
}
