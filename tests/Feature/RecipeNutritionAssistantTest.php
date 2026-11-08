<?php

namespace Tests\Feature;

use App\Models\Food;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecipeNutritionAssistantTest extends TestCase
{
    use RefreshDatabase;

    private Recipe $recipe;

    private Food $egg;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->egg = Food::factory()->create(['bls_code' => 'E111100', 'name' => 'Hühnerei roh', 'kcal' => 135]);
        $this->recipe = Recipe::factory()->create(['servings' => 1]);
        $this->recipe->ingredients()->createMany([
            ['position' => 1, 'amount' => '2', 'unit' => 'Stück', 'name' => 'Eier', 'is_optional' => false],
            ['position' => 2, 'amount' => null, 'unit' => null, 'name' => 'Salz', 'is_optional' => false],
        ]);
    }

    public function test_children_may_not_open_or_save_the_assistant(): void
    {
        $this->actingAs(User::factory()->child()->create());

        $this->get(route('recipes.nutrition.edit', $this->recipe))->assertForbidden();
        $this->put(route('recipes.nutrition.update', $this->recipe), ['ingredients' => []])->assertForbidden();
        $this->get(route('nutrition.overview'))->assertForbidden();
    }

    public function test_assistant_prefills_suggestions_without_saving_them(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('recipes.nutrition.edit', $this->recipe))->assertOk();

        $rows = $response->viewData('rows');
        $this->assertTrue($this->egg->is($rows[0]['food']));
        $this->assertTrue($rows[0]['is_suggestion']);
        $this->assertSame(120.0, $rows[0]['estimate']->grams);
        $this->assertNull($this->recipe->ingredients()->first()->food_id);
        $response->assertSee('Vorschlag')->assertSee('≈ 120 g');
    }

    public function test_saving_stores_food_and_grams(): void
    {
        $this->actingAs(User::factory()->create());
        [$eggs, $salt] = $this->recipe->ingredients()->get();

        $this->put(route('recipes.nutrition.update', $this->recipe), [
            'ingredients' => [
                ['id' => $eggs->id, 'food_id' => $this->egg->id, 'grams' => '115,5'],
                ['id' => $salt->id, 'food_id' => '', 'grams' => ''],
            ],
        ])->assertRedirect(route('recipes.show', $this->recipe));

        $this->assertSame($this->egg->id, $eggs->fresh()->food_id);
        $this->assertSame(115.5, $eggs->fresh()->grams);
        $this->assertNull($salt->fresh()->food_id);
    }

    public function test_ingredients_of_other_recipes_cannot_be_changed(): void
    {
        $this->actingAs(User::factory()->create());
        $foreign = Recipe::factory()->create()->ingredients()->create(['position' => 1, 'name' => 'Mehl', 'is_optional' => false]);

        $this->put(route('recipes.nutrition.update', $this->recipe), [
            'ingredients' => [['id' => $foreign->id, 'food_id' => $this->egg->id]],
        ])->assertSessionHasErrors('ingredients.0.id');

        $this->assertNull($foreign->fresh()->food_id);
    }

    public function test_overview_lists_incomplete_recipes_first(): void
    {
        $this->actingAs(User::factory()->create());
        $done = Recipe::factory()->create(['title' => 'Aaa fertig']);
        $done->ingredients()->create(['position' => 1, 'amount' => '100', 'unit' => 'g', 'name' => 'Ei', 'food_id' => $this->egg->id, 'is_optional' => false]);

        $rows = $this->get(route('nutrition.overview'))->assertOk()->viewData('rows');

        $this->assertTrue($this->recipe->is($rows->first()['recipe']));
        $this->assertTrue($done->is($rows->last()['recipe']));
    }

    public function test_food_search_returns_json(): void
    {
        $this->actingAs(User::factory()->create());

        $this->getJson(route('foods.search', ['q' => 'hühnerei']))
            ->assertOk()
            ->assertJsonPath('0.name', 'Hühnerei roh')
            ->assertJsonPath('0.kcal', 135);

        $this->getJson(route('foods.search', ['q' => 'h']))->assertExactJson([]);
    }
}
