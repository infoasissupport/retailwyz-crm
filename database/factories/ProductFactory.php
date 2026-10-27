<?php

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'unit_id' => Unit::factory(),
            'name' => ucfirst(fake()->words(3, true)),
            'sku' => fake()->unique()->bothify('SKU-#####'),
            'cost_price' => '100.00',
            'selling_price' => '150.00',
            'min_stock' => 5,
            'track_stock' => true,
            'has_variants' => false,
            'status' => ProductStatus::Active,
        ];
    }
}
