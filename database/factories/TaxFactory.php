<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Tax>
 */
class TaxFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'GST 18%',
            'code' => fake()->unique()->bothify('T###'),
            'rate' => 18,
            'is_active' => true,
        ];
    }
}
