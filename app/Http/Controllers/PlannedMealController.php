<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePlannedMealRequest;
use App\Models\PlannedMeal;
use App\Support\Week;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Einträge im persönlichen Plan: Menge ändern und entfernen (nur eigene, PlannedMealPolicy).
 */
class PlannedMealController extends Controller
{
    public function update(UpdatePlannedMealRequest $request, PlannedMeal $plannedMeal): RedirectResponse
    {
        $amount = (float) $request->validated('amount');

        $plannedMeal->fill($plannedMeal->recipe_id !== null
            ? ['servings' => round($amount, 2)]
            : ['grams' => round($amount, 1)])->save();

        return $this->backToDay($plannedMeal);
    }

    public function destroy(PlannedMeal $plannedMeal): RedirectResponse
    {
        Gate::authorize('delete', $plannedMeal);

        $plannedMeal->delete();

        return $this->backToDay($plannedMeal);
    }

    private function backToDay(PlannedMeal $entry): RedirectResponse
    {
        return to_route('personal-plan.show', Week::containing($entry->date->toImmutable()))
            ->withFragment('tag-'.$entry->date->toDateString());
    }
}
