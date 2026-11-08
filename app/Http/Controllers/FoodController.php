<?php

namespace App\Http\Controllers;

use App\Enums\FoodCategory;
use App\Models\Food;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Nährwerttabelle: Lebensmittel suchen und Werte je 100 g ansehen (für alle lesbar).
 */
class FoodController extends Controller
{
    public function index(Request $request): View
    {
        $term = $request->string('q')->trim()->value();
        $category = FoodCategory::tryFrom($request->string('gruppe')->value());

        $foods = Food::query()
            ->search($term)
            ->inCategory($category)
            ->when(blank($term), fn ($query) => $query->orderBy('name'))
            ->paginate(30)
            ->withQueryString();

        return view('foods.index', [
            'foods' => $foods,
            'categories' => FoodCategory::cases(),
            'activeCategory' => $category,
        ]);
    }

    /**
     * Suche für die Lebensmittelauswahl im Zuordnungsassistenten (JSON, höchstens 12 Treffer).
     */
    public function search(Request $request): JsonResponse
    {
        $term = $request->string('q')->trim()->value();

        if (mb_strlen($term) < 2) {
            return response()->json([]);
        }

        // Synonyme zuerst: „Haferflocken“ heißt im BLS „Hafer Flocken“ und würde sonst nicht gefunden
        $synonym = config('nutrition.synonyms')[mb_strtolower($term)] ?? null;
        $synonymFood = $synonym ? Food::query()->firstWhere('bls_code', $synonym) : null;

        $foods = Food::query()
            ->search($term)
            ->limit(12)
            ->get()
            ->when($synonymFood, fn ($foods) => $foods->reject(fn (Food $food) => $food->is($synonymFood))->prepend($synonymFood)->take(12))
            ->map(fn (Food $food) => [
                'id' => $food->id,
                'name' => $food->name,
                'kcal' => $food->kcal === null ? null : (int) round($food->kcal),
                'category' => $food->category?->label(),
            ]);

        return response()->json($foods);
    }
}
