<?php

namespace Database\Factories;

use App\Enums\FoodCategory;
use App\Models\Food;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Food>
 */
class FoodFactory extends Factory
{
    public function definition(): array
    {
        return [
            'bls_code' => 'G'.fake()->unique()->numerify('######'),
            'name' => ucfirst(fake()->unique()->word()).' roh',
            'category' => FoodCategory::Vegetables,
            'kcal' => 40,
            'protein' => 1.5,
            'fat' => 0.3,
            'carbs' => 7,
            'fiber' => 2,
            'sugar' => 4,
            'saturated_fat' => 0.1,
            'salt' => 0.02,
            'source' => 'BLS 4.0',
        ];
    }
}
