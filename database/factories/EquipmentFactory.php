<?php

namespace Database\Factories;

use App\Models\Equipment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Equipment>
 */
class EquipmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => ucfirst(fake()->unique()->word()),
            'is_available' => true,
        ];
    }

    public function unavailable(): static
    {
        return $this->state(['is_available' => false]);
    }
}
