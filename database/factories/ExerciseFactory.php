<?php

namespace Database\Factories;

use App\Enums\ExerciseLevel;
use App\Enums\ExerciseMeasure;
use App\Models\Exercise;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Exercise>
 */
class ExerciseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => ucfirst(fake()->unique()->words(2, true)),
            'category' => 'Ganzkörpertraining',
            'level' => fake()->randomElement(ExerciseLevel::cases()),
            'description' => fake()->sentence(),
            'default_sets' => 3,
            'target' => '10-12',
            'measure' => ExerciseMeasure::Reps,
            'target_min' => 10,
            'target_max' => 12,
            'per_side' => false,
        ];
    }
}
