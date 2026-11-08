<?php

namespace App\Http\Controllers;

use App\Actions\SaveIngredientNutrition;
use App\Http\Requests\UpdateRecipeNutritionRequest;
use App\Models\Recipe;
use App\Support\Nutrition\FoodMatcher;
use App\Support\Nutrition\IngredientNutrition;
use App\Support\Nutrition\IngredientWeight;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Zuordnungsassistent: Zutaten eines Rezepts mit Lebensmitteln verknüpfen und Gewichte prüfen.
 * Noch nicht zugeordnete Zutaten bekommen einen Vorschlag, der erst beim Speichern übernommen wird.
 */
class RecipeNutritionController extends Controller
{
    public function edit(Recipe $recipe, FoodMatcher $matcher): View
    {
        Gate::authorize('update', $recipe);

        $recipe->load('ingredients.food');
        $nutrition = $recipe->nutrition();

        $rows = $nutrition->lines->map(function (IngredientNutrition $line) use ($matcher) {
            $suggestion = $line->food === null && ! $line->ingredient->is_optional ? $matcher->suggest($line->ingredient->name) : null;

            $food = $line->food ?? $suggestion;
            $ingredient = $line->ingredient;

            return [
                'line' => $line,
                'food' => $food,
                'is_suggestion' => $suggestion !== null,
                'is_spice' => FoodMatcher::isNegligible($ingredient->name),
                // Schätzung ohne eingetragenes Gewicht – als Platzhalter im Gewichtsfeld
                'estimate' => IngredientWeight::estimate($ingredient->amount, $ingredient->unit, $ingredient->name, $food),
            ];
        });

        return view('recipes.nutrition', [
            'recipe' => $recipe,
            'nutrition' => $nutrition,
            'rows' => $rows,
        ]);
    }

    public function update(UpdateRecipeNutritionRequest $request, Recipe $recipe, SaveIngredientNutrition $save): RedirectResponse
    {
        $save->handle($recipe, $request->validated('ingredients'));

        return redirect()
            ->route('recipes.show', $recipe)
            ->with('status', 'Nährwert-Zuordnung gespeichert.');
    }
}
