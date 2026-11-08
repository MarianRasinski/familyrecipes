<?php

namespace App\Http\Controllers;

use App\Actions\DeleteRecipe;
use App\Actions\SaveRecipe;
use App\Enums\Difficulty;
use App\Enums\RecipeFilter;
use App\Http\Requests\StoreRecipeRequest;
use App\Http\Requests\UpdateRecipeRequest;
use App\Models\Recipe;
use App\Models\Tag;
use App\Support\LastEaten;
use App\Support\Quantity;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class RecipeController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $filter = RecipeFilter::tryFrom($request->string('eigenschaft')->value());

        $query = Recipe::query()
            ->with(['tags', 'ingredients.food'])
            ->withFavoriteFlag($user)
            ->search($request->string('q')->trim()->value())
            ->withTag($request->string('tag')->value())
            ->when($request->boolean('favoriten'), fn ($query) => $query->favoriteOf($user))
            ->orderByDesc('is_favorite')
            ->orderBy('title');

        // Nährwert-Filter werden aus den Zutaten berechnet → in PHP filtern und selbst blättern
        // (bei einer Familien-Rezeptsammlung von ein paar hundert Rezepten unkritisch).
        $recipes = $filter
            ? $this->paginateCollection(
                $query->get()->filter(fn (Recipe $recipe) => $filter->matches($recipe, $recipe->nutrition()))->values(),
                $request,
            )
            : $query->paginate(12)->withQueryString();

        $tags = Tag::query()
            ->whereHas('recipes')
            ->withCount('recipes')
            ->orderBy('name')
            ->get();

        return view('recipes.index', [
            'recipes' => $recipes,
            'tags' => $tags,
            'filters' => RecipeFilter::cases(),
            'activeFilter' => $filter,
        ]);
    }

    /**
     * @param  Collection<int, Recipe>  $items
     */
    private function paginateCollection(Collection $items, Request $request, int $perPage = 12): LengthAwarePaginator
    {
        $page = max(1, $request->integer('page', 1));

        return (new LengthAwarePaginator($items->forPage($page, $perPage)->values(), $items->count(), $perPage, $page, [
            'path' => $request->url(),
        ]))->withQueryString();
    }

    public function create(): View
    {
        Gate::authorize('create', Recipe::class);

        return view('recipes.create', [
            'recipe' => new Recipe(['servings' => 2]),
            'difficulties' => Difficulty::cases(),
        ]);
    }

    public function store(StoreRecipeRequest $request, SaveRecipe $saveRecipe): RedirectResponse
    {
        $recipe = $saveRecipe->handle(new Recipe, $request->validated(), $request->file('image'), $request->user());

        return to_route('recipes.show', $recipe)->with('status', 'Rezept wurde angelegt.');
    }

    public function show(Request $request, Recipe $recipe): View
    {
        $recipe->load(['ingredients.food', 'steps', 'tags', 'latestRevision']);

        // Portionen umrechnen (?portionen=4); ohne Portionsangabe im Rezept nicht möglich
        $servings = $recipe->servings
            ? min(50, max(1, $request->integer('portionen', $recipe->servings)))
            : null;
        $factor = $servings ? $servings / $recipe->servings : 1.0;

        return view('recipes.show', [
            'recipe' => $recipe,
            'servings' => $servings,
            'amounts' => $recipe->ingredients->mapWithKeys(fn ($ingredient) => [
                $ingredient->id => $factor !== 1.0 && ($quantity = Quantity::parse($ingredient->amount))
                    ? $quantity->scale($factor)->format()
                    : $ingredient->amount,
            ]),
            'nutrition' => $recipe->nutrition(),
            'isFavorite' => $request->user()->hasFavorited($recipe),
            'lastEaten' => LastEaten::forRecipe($recipe->id),
        ]);
    }

    public function edit(Recipe $recipe): View
    {
        Gate::authorize('update', $recipe);

        $recipe->load(['ingredients', 'steps', 'tags']);

        return view('recipes.edit', [
            'recipe' => $recipe,
            'difficulties' => Difficulty::cases(),
        ]);
    }

    public function update(UpdateRecipeRequest $request, Recipe $recipe, SaveRecipe $saveRecipe): RedirectResponse
    {
        $saveRecipe->handle($recipe, $request->validated(), $request->file('image'), $request->user());

        return to_route('recipes.show', $recipe)->with('status', 'Änderungen wurden gespeichert.');
    }

    public function destroy(Request $request, Recipe $recipe, DeleteRecipe $deleteRecipe): RedirectResponse
    {
        Gate::authorize('delete', $recipe);

        $deleteRecipe->handle($recipe, $request->user());

        return to_route('recipes.index')->with('status', "„{$recipe->title}“ wurde gelöscht.");
    }
}
