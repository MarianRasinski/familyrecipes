<?php

namespace Tests\Feature;

use App\Enums\MealSlot;
use App\Models\BodyProfile;
use App\Models\Food;
use App\Models\PlannedMeal;
use App\Models\Recipe;
use App\Models\TrainingDay;
use App\Models\User;
use App\Support\Planning\WeekPlan;
use App\Support\Week;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersonalPlanTest extends TestCase
{
    use RefreshDatabase;

    private Food $oats;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        // Mittwoch, KW 42
        $this->travelTo('2026-10-14 18:00');
        $this->oats = Food::factory()->create(['name' => 'Haferflocken', 'kcal' => 370, 'protein' => 13.5, 'fat' => 7, 'carbs' => 59, 'fiber' => 10]);
    }

    /**
     * Rezept mit 2 Portionen aus 400 g Haferflocken → 740 kcal pro Portion.
     */
    private function recipeWithNutrition(): Recipe
    {
        $recipe = Recipe::factory()->create(['title' => 'Porridge', 'servings' => 2]);
        $recipe->ingredients()->create([
            'position' => 1, 'amount' => '400', 'unit' => 'g', 'name' => 'Haferflocken', 'is_optional' => false,
            'food_id' => $this->oats->id,
        ]);

        return $recipe;
    }

    public function test_week_page_shows_days_and_slots(): void
    {
        $this->actingAs(User::factory()->child()->create());

        $response = $this->get(route('personal-plan.show'))->assertOk();

        $this->assertTrue(Week::current()->equals($response->viewData('plan')->week));
        $this->assertNull($response->viewData('plan')->budget);
        $response->assertSee('Mein Plan')->assertSee('Frühstück')->assertSee('Snacks')->assertDontSee('Profil anlegen');
    }

    public function test_add_recipe_and_food_entries(): void
    {
        $user = User::factory()->create();
        $recipe = $this->recipeWithNutrition();
        $this->actingAs($user);

        $this->get(route('personal-plan.create', ['day' => '2026-10-15', 'mahlzeit' => 'breakfast']))
            ->assertOk()
            ->assertViewHas('slot', MealSlot::Breakfast)
            ->assertViewHas('recipes', fn ($recipes) => $recipes->firstWhere('id', $recipe->id)['kcal'] === 740);

        $this->post(route('planned-meals.store'), [
            'date' => '2026-10-15', 'meal' => 'breakfast', 'type' => 'recipe', 'recipe_id' => $recipe->id, 'servings' => '0,5',
        ])->assertRedirect(route('personal-plan.show', Week::current()).'#tag-2026-10-15');

        $this->post(route('planned-meals.store'), [
            'date' => '2026-10-15', 'meal' => 'breakfast', 'type' => 'food', 'food_id' => $this->oats->id, 'grams' => '50',
            'recipe_id' => $recipe->id, // wird beim Typ „food“ ignoriert
        ])->assertSessionHasNoErrors();

        [$first, $second] = $user->plannedMeals()->orderBy('position')->get();
        $this->assertSame(0.5, $first->servings);
        $this->assertNull($first->food_id);
        $this->assertSame(1, $first->position);
        $this->assertSame(50.0, $second->grams);
        $this->assertNull($second->recipe_id);
        $this->assertSame(2, $second->position);

        $day = WeekPlan::for($user, Week::current())->days[4];
        // 370 kcal (halbe Portion) + 185 kcal (50 g)
        $this->assertSame(555, (int) round($day->total()->kcal));

        $this->get(route('personal-plan.show'))->assertSee('Porridge')->assertSee('0,5 Portionen')->assertSee('50 g')->assertSee('555 kcal');
    }

    public function test_validation_requires_matching_fields(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('planned-meals.store'), ['date' => '2026-10-15', 'meal' => 'brunch', 'type' => 'recipe'])
            ->assertSessionHasErrors(['meal', 'recipe_id', 'servings']);
        $this->post(route('planned-meals.store'), ['date' => '2026-10-15', 'meal' => 'snack', 'type' => 'food', 'grams' => '0'])
            ->assertSessionHasErrors(['food_id', 'grams']);

        $this->assertSame(0, PlannedMeal::query()->count());
    }

    public function test_budget_comparison_uses_training_days(): void
    {
        $user = User::factory()->create();
        BodyProfile::factory()->for($user)->create(['training_days' => 3]);
        PlannedMeal::factory()->for($user)->food($this->oats, 500)->create(['date' => '2026-10-12']);
        $this->actingAs($user);

        $plan = WeekPlan::for($user, Week::current());
        $monday = $plan->days[1];
        $restKcal = $plan->budget->restDayKcal();

        $this->assertFalse($monday->isTrainingDay);
        $this->assertSame((float) $restKcal, $monday->target->kcal);
        $this->assertSame($restKcal - 1850, $monday->remainingKcal());

        $this->put(route('personal-plan.training', '2026-10-12'), ['training' => 1])
            ->assertRedirect(route('personal-plan.show', Week::current()).'#tag-2026-10-12');
        $this->put(route('personal-plan.training', '2026-10-12'), ['training' => 1]);
        $this->assertSame(1, TrainingDay::query()->count());

        $monday = WeekPlan::for($user, Week::current())->days[1];
        $this->assertTrue($monday->isTrainingDay);
        $this->assertSame((float) $plan->budget->trainingDayKcal(), $monday->target->kcal);

        $this->put(route('personal-plan.training', '2026-10-12'), ['training' => 0]);
        $this->assertSame(0, TrainingDay::query()->count());

        $this->get(route('personal-plan.show'))->assertSee('von')->assertSee('Ruhetag');
    }

    public function test_over_budget_is_flagged(): void
    {
        $user = User::factory()->create();
        BodyProfile::factory()->for($user)->create(['kcal_override' => 1500]);
        PlannedMeal::factory()->for($user)->food($this->oats, 500)->create(['date' => '2026-10-14']);

        $day = WeekPlan::for($user, Week::current())->days[3];

        $this->assertTrue($day->isOverBudget());
        $this->assertSame(100, $day->progressPercent());
        $this->actingAs($user)->get(route('personal-plan.show'))->assertSee('350 kcal über Budget');
    }

    public function test_recipe_without_nutrition_is_counted_as_unknown(): void
    {
        $user = User::factory()->create();
        PlannedMeal::factory()->for($user)->create(['date' => '2026-10-14']);

        $day = WeekPlan::for($user, Week::current())->days[3];

        $this->assertSame(1, $day->unknownCount());
        $this->assertSame(0.0, $day->total()->kcal);
        $this->actingAs($user)->get(route('personal-plan.show'))->assertSee('noch keine Nährwerte');
    }

    public function test_update_amount_and_delete(): void
    {
        $user = User::factory()->create();
        $entry = PlannedMeal::factory()->for($user)->food($this->oats, 50)->create();
        $this->actingAs($user);

        $this->patch(route('planned-meals.update', $entry), ['amount' => '80,5'])->assertRedirect();
        $this->assertSame(80.5, $entry->fresh()->grams);

        $this->patch(route('planned-meals.update', $entry), ['amount' => '0'])->assertSessionHasErrors('amount');

        $this->delete(route('planned-meals.destroy', $entry))->assertRedirect();
        $this->assertModelMissing($entry);
    }

    public function test_everyone_only_sees_and_changes_their_own_plan(): void
    {
        $owner = User::factory()->create();
        $entry = PlannedMeal::factory()->for($owner)->food($this->oats, 50)->create(['date' => '2026-10-14']);
        $this->actingAs(User::factory()->admin()->create());

        $this->get(route('personal-plan.show'))->assertOk()->assertDontSee('Haferflocken');
        $this->patch(route('planned-meals.update', $entry), ['amount' => '100'])->assertForbidden();
        $this->delete(route('planned-meals.destroy', $entry))->assertForbidden();
        $this->assertSame(50.0, $entry->fresh()->grams);
    }

    public function test_invalid_day_is_not_found(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/mein-plan/tag/2026-02-30/hinzufuegen')->assertNotFound();
    }
}
