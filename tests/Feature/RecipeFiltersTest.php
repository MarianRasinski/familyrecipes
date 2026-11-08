<?php

namespace Tests\Feature;

use App\Enums\RecipeFilter;
use App\Models\Food;
use App\Models\Recipe;
use App\Models\User;
use App\Support\Quantity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Nährwerte auf Rezeptkarten, Filter High Protein/Low Carb/Schnell und Portionen umrechnen.
 */
class RecipeFiltersTest extends TestCase
{
    use RefreshDatabase;

    private Recipe $chicken;

    private Recipe $pasta;

    private Recipe $unknown;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $breast = Food::factory()->create(['name' => 'Hähnchenbrust', 'kcal' => 110, 'protein' => 23, 'fat' => 2, 'carbs' => 0, 'fiber' => 0]);
        $noodles = Food::factory()->create(['name' => 'Nudeln', 'kcal' => 350, 'protein' => 12, 'fat' => 1.5, 'carbs' => 70, 'fiber' => 3]);

        // 2 Portionen à 200 g Hähnchen: 46 g Eiweiß, 0 g KH, 220 kcal, 15 Minuten
        $this->chicken = Recipe::factory()->create(['title' => 'Hähnchenpfanne', 'servings' => 2, 'prep_minutes' => 5, 'cook_minutes' => 10]);
        $this->chicken->ingredients()->create(['position' => 1, 'amount' => '400', 'unit' => 'g', 'name' => 'Hähnchenbrust', 'is_optional' => false, 'food_id' => $breast->id]);

        // 2 Portionen à 125 g Nudeln: 15 g Eiweiß, 87,5 g KH, 60 Minuten
        $this->pasta = Recipe::factory()->create(['title' => 'Nudelauflauf', 'servings' => 2, 'prep_minutes' => 20, 'cook_minutes' => 40]);
        $this->pasta->ingredients()->create(['position' => 1, 'amount' => '250', 'unit' => 'g', 'name' => 'Nudeln', 'is_optional' => false, 'food_id' => $noodles->id]);

        // Ohne Zuordnung: taucht bei Nährwert-Filtern nie auf
        $this->unknown = Recipe::factory()->create(['title' => 'Omas Geheimrezept', 'servings' => 4, 'prep_minutes' => 10, 'cook_minutes' => null]);
        $this->unknown->ingredients()->create(['position' => 1, 'amount' => '1', 'unit' => 'Prise', 'name' => 'Liebe', 'is_optional' => false]);

        $this->actingAs(User::factory()->create());
    }

    /**
     * @return list<string>
     */
    private function titlesFor(?string $filter): array
    {
        return $this->get(route('recipes.index', array_filter(['eigenschaft' => $filter])))
            ->assertOk()
            ->viewData('recipes')
            ->pluck('title')
            ->sort()
            ->values()
            ->all();
    }

    public function test_cards_show_nutrition_per_serving(): void
    {
        $this->get(route('recipes.index'))
            ->assertSee('220 kcal')
            ->assertSee('46 g Eiweiß')
            ->assertSee('pro Portion');
    }

    public function test_high_protein_filter(): void
    {
        $this->assertSame(['Hähnchenpfanne'], $this->titlesFor('high-protein'));
    }

    public function test_low_carb_filter_needs_complete_recipes(): void
    {
        $this->assertSame(['Hähnchenpfanne'], $this->titlesFor('low-carb'));

        // Eine Zutat ohne Lebensmittel → nicht mehr sicher „Low Carb“
        $this->chicken->ingredients()->create(['position' => 2, 'amount' => '200', 'unit' => 'g', 'name' => 'Reis', 'is_optional' => false]);
        $this->assertSame([], $this->titlesFor('low-carb'));
        $this->assertSame(['Hähnchenpfanne'], $this->titlesFor('high-protein'));
    }

    public function test_quick_filter_uses_total_time(): void
    {
        $this->assertSame(['Hähnchenpfanne', 'Omas Geheimrezept'], $this->titlesFor('schnell'));
    }

    public function test_unknown_filter_shows_everything_and_filters_combine_with_search(): void
    {
        $this->assertCount(3, $this->titlesFor('quatsch'));

        $titles = $this->get(route('recipes.index', ['eigenschaft' => 'schnell', 'q' => 'Oma']))->viewData('recipes')->pluck('title')->all();
        $this->assertSame(['Omas Geheimrezept'], $titles);
    }

    public function test_filtered_list_is_paginated(): void
    {
        Recipe::factory()->count(13)->create(['prep_minutes' => 10, 'cook_minutes' => 0]);

        $page1 = $this->get(route('recipes.index', ['eigenschaft' => 'schnell']))->viewData('recipes');
        $page2 = $this->get(route('recipes.index', ['eigenschaft' => 'schnell', 'page' => 2]))->viewData('recipes');

        $this->assertSame(15, $page1->total());
        $this->assertCount(12, $page1);
        $this->assertCount(3, $page2);
        $this->assertStringContainsString('eigenschaft=schnell', $page1->nextPageUrl());
    }

    public function test_filter_descriptions_come_from_config(): void
    {
        config(['nutrition.recipe_filters.quick_max_minutes' => 12]);

        $this->assertSame('fertig in höchstens 12 Minuten', RecipeFilter::Quick->description());
        $this->assertSame(['Omas Geheimrezept'], $this->titlesFor('schnell'));
    }

    public function test_servings_can_be_scaled_on_the_recipe_page(): void
    {
        $this->pasta->ingredients()->create(['position' => 2, 'amount' => '1/2', 'unit' => 'TL', 'name' => 'Salz', 'is_optional' => false]);
        $this->pasta->ingredients()->create(['position' => 3, 'amount' => 'etwas', 'unit' => null, 'name' => 'Käse', 'is_optional' => false]);

        $response = $this->get(route('recipes.show', [$this->pasta, 'portionen' => 3]))->assertOk();

        $this->assertSame(3, $response->viewData('servings'));
        $amounts = $response->viewData('amounts')->values()->all();
        $this->assertSame(['375', '0,75', 'etwas'], $amounts);
        $response->assertSee('375 g')->assertSee('Original: 2');

        // Ohne Parameter: Originalmengen
        $this->get(route('recipes.show', $this->pasta))->assertViewHas('servings', 2)->assertSee('250 g')->assertDontSee('Original: 2');

        // Grenzen
        $this->get(route('recipes.show', [$this->pasta, 'portionen' => 0]))->assertViewHas('servings', 1);
        $this->get(route('recipes.show', [$this->pasta, 'portionen' => 999]))->assertViewHas('servings', 50);
    }

    public function test_quantity_scale_keeps_ranges(): void
    {
        $this->assertSame('750–900', Quantity::parse('500-600')->scale(1.5)->format());
    }
}
