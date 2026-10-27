<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Exceptions\BusinessRuleException;
use App\Models\Product;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Facades\DB;

class ProductService
{
    public function __construct(private InventoryService $inventory) {}

    /**
     * @param  array<string, mixed>  $data  validated product fields, plus optional
     *     `variants` (list of variant rows) and `opening_stock` / `opening_branch_id`
     */
    public function create(array $data, User $user): Product
    {
        return DB::transaction(function () use ($data, $user) {
            $product = new Product;
            $product->fill($this->productFields($data));
            $product->forceFill(['created_by' => $user->id])->save();

            $this->syncVariants($product, $data['variants'] ?? []);

            $opening = $data['opening_stock'] ?? null;
            if ($opening !== null && Money::cmp($opening, '0') > 0 && ! $product->has_variants) {
                $this->inventory->move(
                    (int) ($data['opening_branch_id'] ?? $user->branch_id),
                    $product->id, null, Money::normalize($opening),
                    StockMovementType::Opening, $product, $product->cost_price, 'Opening stock',
                );
            }

            return $product;
        });
    }

    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {
            $product->fill($this->productFields($data))->save();
            $this->syncVariants($product, $data['variants'] ?? []);

            return $product;
        });
    }

    /**
     * Create/update submitted variants. Variants missing from the submission
     * are deactivated rather than deleted, since stock history may reference them.
     */
    private function syncVariants(Product $product, array $variants): void
    {
        if (! $product->has_variants) {
            return;
        }

        $keep = [];
        foreach ($variants as $row) {
            $attributes = [
                'name' => $row['name'],
                'sku' => $row['sku'],
                'barcode' => $row['barcode'] ?? null,
                'options' => $row['options'] ?? null,
                'cost_price' => $row['cost_price'] ?? null,
                'selling_price' => $row['selling_price'] ?? null,
                'is_active' => (bool) ($row['is_active'] ?? true),
            ];

            if (! empty($row['id'])) {
                $variant = $product->variants()->find($row['id']) ?? throw new BusinessRuleException('Variant does not belong to this product.');
                $variant->update($attributes);
            } else {
                $variant = $product->variants()->create($attributes);
            }

            $keep[] = $variant->id;
        }

        $product->variants()->whereNotIn('id', $keep)->update(['is_active' => false]);
    }

    private function productFields(array $data): array
    {
        return array_intersect_key($data, array_flip((new Product)->getFillable()));
    }
}
