<?php

namespace Database\Factories;

use App\Models\MuscleGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MuscleGroup>
 */
class MuscleGroupFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => ucfirst(fake()->unique()->word()),
        ];
    }
}
