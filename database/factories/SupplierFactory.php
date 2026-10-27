<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Supplier>
 */
class SupplierFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('SUP-####'),
            'name' => fake()->company(),
            'contact_person' => fake()->name(),
            'phone' => fake()->numerify('9#########'),
            'email' => fake()->companyEmail(),
            'address' => fake()->address(),
            'is_active' => true,
        ];
    }
}
