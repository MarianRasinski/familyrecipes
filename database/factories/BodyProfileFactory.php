<?php

namespace Database\Factories;

use App\Enums\ActivityLevel;
use App\Enums\FitnessGoal;
use App\Enums\Sex;
use App\Enums\TrainingType;
use App\Models\BodyProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BodyProfile>
 */
class BodyProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'sex' => Sex::Male,
            'birth_date' => now()->subYears(35)->subMonth()->toDateString(),
            'height_cm' => 180,
            'weight_kg' => 80,
            'activity_level' => ActivityLevel::Light,
            'goal' => FitnessGoal::Maintain,
            'training_days' => 0,
            'training_minutes' => 60,
            'training_type' => TrainingType::Strength,
            'kcal_override' => null,
        ];
    }
}
