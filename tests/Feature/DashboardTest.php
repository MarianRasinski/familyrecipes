<?php

namespace Tests\Feature;

use App\Enums\MealSlot;
use App\Models\BodyProfile;
use App\Models\Food;
use App\Models\PlannedMeal;
use App\Models\TrainingDay;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * „Mein Tag“ auf der Startseite: persönliche Tagesziele und heutige Mahlzeiten.
 */
class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private Food $oats;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->travelTo('2026-10-15 08:00'); // Donnerstag, KW 42
        $this->oats = Food::factory()->create(['name' => 'Haferflocken', 'kcal' => 370, 'protein' => 13.5, 'fat' => 7, 'carbs' => 59, 'fiber' => 10]);
    }

    public function test_empty_day_offers_planning(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/')
            ->assertOk()
            ->assertSee('Mein Tag')
            ->assertSee('Für heute hast du noch nichts geplant.')
            ->assertSee(route('personal-plan.create', ['day' => '2026-10-15', 'mahlzeit' => 'breakfast']), false)
            ->assertSee('Profil &amp; Ziele', false);
    }

    public function test_shows_todays_meals_with_progress_rings(): void
    {
        $user = User::factory()->create();
        BodyProfile::factory()->for($user)->create(['training_days' => 3]);
        TrainingDay::query()->forceCreate(['user_id' => $user->id, 'date' => '2026-10-15']);
        PlannedMeal::factory()->for($user)->food($this->oats, 100)->create(['date' => '2026-10-15', 'meal' => MealSlot::Breakfast]);
        PlannedMeal::factory()->for($user)->food($this->oats, 999)->create(['date' => '2026-10-14']); // gestern, zählt nicht

        $response = $this->actingAs($user)->get('/')->assertOk();

        $myDay = $response->viewData('myDay');
        $this->assertTrue($myDay->isTrainingDay);
        $this->assertSame(370.0, $myDay->total()->kcal);

        $remaining = (int) $myDay->target->kcal - 370;
        $response->assertSee('🏋️ Trainingstag')
            ->assertSee('Haferflocken (100 g)')
            ->assertSee('370 kcal')
            ->assertSee('Noch '.number_format($remaining, 0, ',', '.').' kcal übrig')
            ->assertSee('aria-label="Kalorien: 370 von', false)
            ->assertSee('＋ Mittagessen')
            ->assertSee('Diese Woche');
    }

    public function test_children_see_their_plan_without_budget(): void
    {
        $child = User::factory()->child()->create();
        PlannedMeal::factory()->for($child)->food($this->oats, 50)->create(['date' => '2026-10-15', 'meal' => MealSlot::Snack]);

        $this->actingAs($child)->get('/')
            ->assertOk()
            ->assertSee('Heute geplant:')
            ->assertSee('185 kcal')
            ->assertDontSee('Kalorien:')
            ->assertDontSee('Profil &amp; Ziele', false);
    }

    public function test_dashboard_only_shows_own_plan(): void
    {
        $other = User::factory()->create();
        PlannedMeal::factory()->for($other)->food($this->oats, 100)->create(['date' => '2026-10-15']);

        $this->actingAs(User::factory()->create())->get('/')
            ->assertSee('Für heute hast du noch nichts geplant.')
            ->assertDontSee('Haferflocken');
    }
}
