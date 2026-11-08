<?php

namespace App\Actions;

use App\Models\Recipe;
use Illuminate\Support\Facades\DB;

/**
 * Speichert die Lebensmittel-Zuordnung und eingetragene Gewichte der Zutaten eines Rezepts.
 * Zutaten selbst (Name, Menge) bleiben unverändert; das Änderungsprotokoll betrifft das nicht.
 */
class SaveIngredientNutrition
{
    /**
     * @param  list<array{id: int|string, food_id?: int|string|null, grams?: float|string|null}>  $rows
     */
    public function handle(Recipe $recipe, array $rows): void
    {
        $ingredients = $recipe->ingredients()->get()->keyBy('id');

        DB::transaction(function () use ($rows, $ingredients) {
            foreach ($rows as $row) {
                $ingredient = $ingredients->get((int) $row['id']);

                $ingredient?->fill([
                    'food_id' => filled($row['food_id'] ?? null) ? (int) $row['food_id'] : null,
                    'grams' => filled($row['grams'] ?? null) ? round((float) $row['grams'], 1) : null,
                ])->save();
            }
        });
    }
}
