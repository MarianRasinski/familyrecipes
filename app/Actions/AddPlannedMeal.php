<?php

namespace App\Actions;

use App\Models\PlannedMeal;
use App\Models\User;

/**
 * Fügt einen Eintrag (Rezept oder Lebensmittel) am Ende einer Mahlzeit im persönlichen Plan an.
 */
class AddPlannedMeal
{
    /**
     * @param  array{date: string, meal: string, recipe_id?: int|null, servings?: float|null, food_id?: int|null, grams?: float|null}  $data
     */
    public function handle(User $user, array $data): PlannedMeal
    {
        $isRecipe = filled($data['recipe_id'] ?? null);

        $position = (int) $user->plannedMeals()
            ->whereDate('date', $data['date'])
            ->where('meal', $data['meal'])
            ->max('position') + 1;

        return $user->plannedMeals()->create([
            'date' => $data['date'],
            'meal' => $data['meal'],
            'position' => $position,
            'recipe_id' => $isRecipe ? (int) $data['recipe_id'] : null,
            'servings' => $isRecipe ? round((float) $data['servings'], 2) : null,
            'food_id' => $isRecipe ? null : (int) $data['food_id'],
            'grams' => $isRecipe ? null : round((float) $data['grams'], 1),
        ]);
    }
}
