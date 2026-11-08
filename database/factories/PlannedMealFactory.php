<?php

namespace Database\Factories;

use App\Enums\MealSlot;
use App\Models\Food;
use App\Models\PlannedMeal;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlannedMeal>
 */
class PlannedMealFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'date' => today()->toDateString(),
            'meal' => MealSlot::Lunch,
            'position' => 0,
            'recipe_id' => Recipe::factory(),
            'servings' => 1,
            'food_id' => null,
            'grams' => null,
        ];
    }

    public function food(?Food $food = null, float $grams = 100): static
    {
        return $this->state([
            'recipe_id' => null,
            'servings' => null,
            'food_id' => $food ?? Food::factory(),
            'grams' => $grams,
        ]);
    }
}
