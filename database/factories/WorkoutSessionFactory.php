<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\WorkoutSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkoutSession>
 */
class WorkoutSessionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'workout_id' => null,
            'name' => 'Ganzkörper A',
            'date' => today()->toDateString(),
            'finished_at' => null,
            'notes' => null,
        ];
    }

    public function finished(): static
    {
        return $this->state(fn () => ['finished_at' => now()]);
    }
}
