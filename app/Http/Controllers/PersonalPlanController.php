<?php

namespace App\Http\Controllers;

use App\Actions\AddPlannedMeal;
use App\Actions\ToggleTrainingDay;
use App\Enums\MealSlot;
use App\Http\Requests\StorePlannedMealRequest;
use App\Models\Food;
use App\Models\Recipe;
use App\Support\Planning\WeekPlan;
use App\Support\Week;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * „Mein Plan“: persönlicher Wochenplaner mit mehreren Mahlzeiten pro Tag und Abgleich mit dem Tagesbudget.
 * Jede Person sieht und plant nur ihren eigenen Plan (keine Benutzer-ID in den Routen).
 */
class PersonalPlanController extends Controller
{
    public function show(Request $request, ?Week $week = null): View
    {
        $week ??= Week::current();

        return view('personal-plan.show', [
            'plan' => WeekPlan::for($request->user(), $week),
            'slots' => MealSlot::cases(),
        ]);
    }

    public function create(Request $request, string $day): View
    {
        $date = self::parseDay($day);
        $user = $request->user();
        $favoriteIds = $user->favoriteRecipes()->pluck('recipes.id');

        $recipes = Recipe::query()
            ->with('ingredients.food')
            ->orderBy('title')
            ->get()
            ->map(function (Recipe $recipe) use ($favoriteIds) {
                $nutrition = $recipe->nutrition();

                return [
                    'id' => $recipe->id,
                    'title' => $recipe->title,
                    'emoji' => $recipe->emoji ?: '🍽️',
                    'favorite' => $favoriteIds->contains($recipe->id),
                    'kcal' => $nutrition->hasData() ? (int) round($nutrition->perServing()->kcal) : null,
                ];
            });

        return view('personal-plan.create', [
            'date' => $date,
            'week' => Week::containing($date),
            'slot' => MealSlot::tryFrom($request->string('mahlzeit')->value()) ?? MealSlot::Lunch,
            'slots' => MealSlot::cases(),
            'recipes' => $recipes,
            // Nach einem Validierungsfehler bleibt das gewählte Lebensmittel ausgewählt
            'oldFood' => ($food = Food::query()->find($request->old('food_id')))
                ? ['id' => $food->id, 'name' => $food->name, 'kcal' => $food->kcal === null ? null : (int) round($food->kcal)]
                : null,
        ]);
    }

    public function store(StorePlannedMealRequest $request, AddPlannedMeal $add): RedirectResponse
    {
        $entry = $add->handle($request->user(), $request->validated());

        return to_route('personal-plan.show', Week::containing($entry->date->toImmutable()))
            ->withFragment('tag-'.$entry->date->toDateString())
            ->with('status', 'Eingeplant: '.$entry->loadMissing(['recipe', 'food'])->title());
    }

    public function updateTrainingDay(Request $request, string $day, ToggleTrainingDay $toggle): RedirectResponse
    {
        $date = self::parseDay($day);
        $toggle->handle($request->user(), $date, $request->boolean('training'));

        return to_route('personal-plan.show', Week::containing($date))->withFragment('tag-'.$date->toDateString());
    }

    /**
     * „2026-11-09“ → Datum; ungültige Tage ergeben ein 404.
     */
    private static function parseDay(string $day): CarbonImmutable
    {
        $date = CarbonImmutable::createFromFormat('!Y-m-d', $day);

        if (! $date || $date->toDateString() !== $day) {
            throw new NotFoundHttpException;
        }

        return $date;
    }
}
