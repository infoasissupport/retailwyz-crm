<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * The single place where stock quantities change.
 *
 * Every change writes one stock_movements row and updates the cached
 * stock_levels row in the same transaction. The product row is locked first
 * so concurrent sales of the same product are serialised and cannot oversell.
 */
class InventoryService
{
    public function __construct(private SettingsService $settings) {}

    /**
     * Apply a signed quantity change. Must be called inside a DB transaction
     * owned by the caller, so the movement commits or rolls back together
     * with the document that caused it.
     */
    public function move(
        int $branchId,
        int $productId,
        ?int $variantId,
        string $quantity,
        StockMovementType $type,
        ?Model $reference = null,
        ?string $unitCost = null,
        ?string $notes = null,
        ?bool $allowNegative = null,
    ): ?StockMovement {
        if (DB::transactionLevel() === 0) {
            throw new LogicException('Stock changes must run inside a database transaction.');
        }

        if (Money::isZero($quantity)) {
            return null;
        }

        $product = Product::withTrashed()->whereKey($productId)->lockForUpdate()->firstOrFail();

        if (! $product->track_stock) {
            return null;
        }

        $level = $this->lockLevel($branchId, $productId, $variantId);
        $newBalance = Money::add($level->quantity, $quantity);

        $allowNegative ??= $this->settings->bool('allow_negative_stock');
        if (Money::cmp($quantity, '0') < 0 && Money::cmp($newBalance, '0') < 0 && ! $allowNegative) {
            throw InsufficientStockException::for(
                $product->name,
                Money::round($level->quantity, 3),
                Money::round(Money::mul($quantity, '-1'), 3),
            );
        }

        $level->forceFill(['quantity' => $newBalance])->save();

        $movement = new StockMovement;
        $movement->forceFill([
            'branch_id' => $branchId,
            'product_id' => $productId,
            'product_variant_id' => $variantId,
            'type' => $type,
            'quantity' => $quantity,
            'balance_after' => $newBalance,
            'unit_cost' => $unitCost,
            'reference_type' => $reference?->getMorphClass(),
            'reference_id' => $reference?->getKey(),
            'notes' => $notes,
            'created_by' => auth()->id(),
        ])->save();

        return $movement;
    }

    public function available(int $branchId, int $productId, ?int $variantId = null): string
    {
        return (string) (StockLevel::query()
            ->where('branch_id', $branchId)
            ->where('product_id', $productId)
            ->where('product_variant_id', $variantId)
            ->value('quantity') ?? '0');
    }

    private function lockLevel(int $branchId, int $productId, ?int $variantId): StockLevel
    {
        $level = StockLevel::query()
            ->where('branch_id', $branchId)
            ->where('product_id', $productId)
            ->where('product_variant_id', $variantId)
            ->lockForUpdate()
            ->first();

        if ($level) {
            return $level;
        }

        // Safe without a unique index: the product row lock above serialises creation.
        $level = new StockLevel;
        $level->forceFill([
            'branch_id' => $branchId,
            'product_id' => $productId,
            'product_variant_id' => $variantId,
            'quantity' => '0',
        ])->save();

        return $level;
    }
}
