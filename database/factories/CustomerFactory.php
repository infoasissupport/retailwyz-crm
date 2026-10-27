<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('CUS-####'),
            'name' => fake()->name(),
            'phone' => fake()->numerify('9#########'),
            'email' => fake()->safeEmail(),
            'address' => fake()->address(),
            'is_active' => true,
        ];
    }
}
