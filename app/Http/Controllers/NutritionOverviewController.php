<?php

namespace App\Http\Controllers;

use App\Models\Recipe;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Übersicht: Welche Rezepte haben vollständige, geschätzte oder noch keine Nährwerte?
 * Unvollständige zuerst, damit man sie der Reihe nach zuordnen kann.
 */
class NutritionOverviewController extends Controller
{
    public function __invoke(): View
    {
        Gate::authorize('create', Recipe::class);

        $recipes = Recipe::query()
            ->with('ingredients.food')
            ->orderBy('title')
            ->get()
            ->map(fn (Recipe $recipe) => ['recipe' => $recipe, 'nutrition' => $recipe->nutrition()])
            ->sortBy(fn (array $row) => [$row['nutrition']->isComplete() ? 1 : 0, $row['recipe']->title])
            ->values();

        return view('nutrition.overview', [
            'rows' => $recipes,
            'incomplete' => $recipes->reject(fn (array $row) => $row['nutrition']->isComplete())->count(),
        ]);
    }
}
