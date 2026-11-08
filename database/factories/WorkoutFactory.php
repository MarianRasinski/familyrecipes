<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Workout;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Workout>
 */
class WorkoutFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => 'Ganzkörper '.fake()->randomElement(['A', 'B', 'C']),
            'notes' => null,
        ];
    }
}
