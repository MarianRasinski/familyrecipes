<?php

namespace Tests\Feature;

use App\Actions\SaveRecipe;
use App\Models\Food;
use App\Models\Recipe;
use App\Models\User;
use App\Support\Nutrition\IngredientNutrition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecipeNutritionTest extends TestCase
{
    use RefreshDatabase;

    private Food $quark;

    private Food $oil;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->quark = Food::factory()->create(['name' => 'Magerquark', 'kcal' => 66, 'protein' => 12, 'fat' => 0.3, 'carbs' => 4, 'fiber' => 0]);
        $this->oil = Food::factory()->create(['name' => 'Olivenöl', 'category' => 'Q', 'kcal' => 899, 'protein' => 0, 'fat' => 99.9, 'carbs' => 0, 'fiber' => 0]);
    }

    public function test_nutrients_are_summed_and_divided_into_servings(): void
    {
        $recipe = $this->recipe(servings: 2, ingredients: [
            ['amount' => '500', 'unit' => 'g', 'name' => 'Magerquark', 'food_id' => $this->quark->id],
            ['amount' => '10', 'unit' => 'g', 'name' => 'Olivenöl', 'food_id' => $this->oil->id],
            ['amount' => null, 'unit' => null, 'name' => 'Salz'],
            ['amount' => '1', 'unit' => 'Stück', 'name' => 'Zitrone', 'is_optional' => true],
        ]);

        $nutrition = $recipe->nutrition();

        // 500 g Quark = 330 kcal / 60 g Eiweiß, 10 g Öl = 89,9 kcal → durch 2
        $this->assertSame(['kcal' => 210, 'protein' => 30.0, 'fat' => 5.7, 'carbs' => 10.0, 'fiber' => 0.0], $nutrition->perServing()->rounded());
        $this->assertTrue($nutrition->isComplete());
        $this->assertFalse($nutrition->isEstimated());
        $this->assertSame(2, $nutrition->countedCount());
        $this->assertSame([IngredientNutrition::COUNTED, IngredientNutrition::COUNTED, IngredientNutrition::NEGLIGIBLE, IngredientNutrition::OPTIONAL],
            $nutrition->lines->pluck('status')->all());
    }

    public function test_estimated_amounts_and_missing_ingredients_mark_the_result_as_estimate(): void
    {
        $recipe = $this->recipe(servings: 1, ingredients: [
            ['amount' => '2', 'unit' => 'EL', 'name' => 'Olivenöl', 'food_id' => $this->oil->id],
            ['amount' => '100', 'unit' => 'g', 'name' => 'Drachenfrucht'],
            ['amount' => null, 'unit' => null, 'name' => 'Tomaten', 'food_id' => $this->quark->id],
        ]);

        $nutrition = $recipe->nutrition();

        $this->assertSame(180, $nutrition->perServing()->rounded()['kcal']); // 2 EL Öl ≈ 20 g
        $this->assertTrue($nutrition->isEstimated());
        $this->assertFalse($nutrition->isComplete());
        $this->assertSame(2, $nutrition->missingCount());
        $this->assertSame([IngredientNutrition::COUNTED, IngredientNutrition::NO_FOOD, IngredientNutrition::NO_WEIGHT], $nutrition->lines->pluck('status')->all());
    }

    public function test_entered_grams_replace_the_estimate(): void
    {
        $recipe = $this->recipe(servings: 1, ingredients: [
            ['amount' => '2', 'unit' => 'EL', 'name' => 'Olivenöl', 'food_id' => $this->oil->id, 'grams' => 30],
        ]);

        $this->assertSame(270, $recipe->nutrition()->total->rounded()['kcal']);
        $this->assertFalse($recipe->nutrition()->isEstimated());
    }

    public function test_editing_a_recipe_keeps_food_links_and_resets_grams_when_the_amount_changes(): void
    {
        $recipe = $this->recipe(servings: 2, ingredients: [
            ['amount' => '500', 'unit' => 'g', 'name' => 'Magerquark', 'food_id' => $this->quark->id, 'grams' => 500],
            ['amount' => '2', 'unit' => 'EL', 'name' => 'Olivenöl', 'food_id' => $this->oil->id, 'grams' => 18],
        ]);

        app(SaveRecipe::class)->handle($recipe, [
            'title' => $recipe->title,
            'servings' => 2,
            'ingredients' => [
                ['amount' => '500', 'unit' => 'g', 'name' => 'Magerquark'],
                ['amount' => '3', 'unit' => 'EL', 'name' => 'olivenöl'],
                ['amount' => '1', 'unit' => 'Stück', 'name' => 'Banane'],
            ],
            'steps' => ['Verrühren.'],
        ]);

        $ingredients = $recipe->ingredients()->get();

        $this->assertSame([$this->quark->id, $this->oil->id, null], $ingredients->pluck('food_id')->all());
        $this->assertSame([500.0, null, null], $ingredients->pluck('grams')->all());
    }

    public function test_recipe_page_shows_values_per_serving(): void
    {
        $this->actingAs(User::factory()->create());
        $recipe = $this->recipe(servings: 2, ingredients: [
            ['amount' => '500', 'unit' => 'g', 'name' => 'Magerquark', 'food_id' => $this->quark->id],
        ]);

        $this->get(route('recipes.show', $recipe))
            ->assertOk()
            ->assertSee('Nährwerte pro Portion')
            ->assertSee('165 kcal')
            ->assertSee('Berechnet aus 1 von 1 Zutaten')
            ->assertSee(route('recipes.nutrition.edit', $recipe));
    }

    public function test_recipe_page_invites_adults_to_calculate_and_hides_it_from_children(): void
    {
        $recipe = $this->recipe(servings: 2, ingredients: [['amount' => '500', 'unit' => 'g', 'name' => 'Magerquark']]);

        $this->actingAs(User::factory()->create())->get(route('recipes.show', $recipe))->assertSee('Nährwerte berechnen');
        $this->actingAs(User::factory()->child()->create())->get(route('recipes.show', $recipe))->assertDontSee('Nährwerte berechnen');
    }

    /**
     * @param  list<array<string, mixed>>  $ingredients
     */
    private function recipe(int $servings, array $ingredients): Recipe
    {
        $recipe = Recipe::factory()->create(['servings' => $servings]);

        foreach ($ingredients as $i => $row) {
            $recipe->ingredients()->create(['position' => $i + 1, 'is_optional' => false, ...$row]);
        }

        return $recipe->load('ingredients.food');
    }
}
