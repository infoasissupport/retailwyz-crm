<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Sellable variation of a product (size, colour, wattage, model...). Price
 * and cost fall back to the parent product when null.
 */
class ProductVariant extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    protected $fillable = ['name', 'sku', 'barcode', 'options', 'cost_price', 'selling_price', 'is_active'];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'cost_price' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function stockLevels(): HasMany
    {
        return $this->hasMany(StockLevel::class);
    }

    public function effectiveSellingPrice(): string
    {
        return $this->selling_price ?? $this->product->selling_price;
    }

    public function effectiveCostPrice(): string
    {
        return $this->cost_price ?? $this->product->cost_price;
    }
}
