<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Unit>
 */
class UnitFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Piece',
            'short_name' => fake()->unique()->lexify('u???'),
            'allow_decimal' => false,
            'is_active' => true,
        ];
    }
}
