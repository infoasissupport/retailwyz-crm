<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Branch>
 */
class BranchFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('BR-###')),
            'name' => fake()->city().' Branch',
            'phone' => fake()->phoneNumber(),
            'is_active' => true,
        ];
    }
}
