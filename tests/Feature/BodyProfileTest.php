<?php

namespace Tests\Feature;

use App\Enums\FitnessGoal;
use App\Models\BodyProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BodyProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->travelTo('2026-11-08 12:00');
    }

    /**
     * @return array<string, mixed>
     */
    private function validData(array $overrides = []): array
    {
        return [
            'sex' => 'male',
            'birth_date' => '1991-01-01',
            'height_cm' => '180',
            'weight_kg' => '80,0',
            'activity_level' => 'light',
            'goal' => 'lose',
            'training_days' => '3',
            'training_minutes' => '60',
            'training_type' => 'strength',
            'kcal_override' => '',
            ...$overrides,
        ];
    }

    public function test_children_have_no_profile(): void
    {
        $this->actingAs(User::factory()->child()->create());

        $this->get(route('body-profile.edit'))->assertForbidden();
        $this->put(route('body-profile.update'), $this->validData())->assertForbidden();
        $this->get(route('home'))->assertDontSee('Profil &amp; Ziele', false);
    }

    public function test_adult_sees_empty_form_and_menu_entry(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('body-profile.edit'))
            ->assertOk()
            ->assertViewHas('budget', null)
            ->assertSee('Budget berechnen')
            ->assertDontSee('Dein Tagesbudget');
        $this->get(route('home'))->assertSee('Profil &amp; Ziele', false);
    }

    public function test_saving_creates_profile_and_shows_budget(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->put(route('body-profile.update'), $this->validData())
            ->assertRedirect(route('body-profile.edit'))
            ->assertSessionHasNoErrors();

        $profile = $user->bodyProfile()->firstOrFail();
        $this->assertSame(80.0, $profile->weight_kg);
        $this->assertSame(FitnessGoal::Lose, $profile->goal);
        $this->assertNull($profile->kcal_override);

        $response = $this->get(route('body-profile.edit'))->assertOk();
        $this->assertSame(2310, $response->viewData('budget')->restDayKcal());
        $response->assertSee('Dein Tagesbudget')->assertSee('2.310')->assertSee('2.630')->assertSee('So wird gerechnet');
    }

    public function test_saving_again_updates_the_same_profile(): void
    {
        $user = User::factory()->create();
        BodyProfile::factory()->for($user)->create();
        $this->actingAs($user);

        $this->put(route('body-profile.update'), $this->validData(['kcal_override' => '2000', 'training_days' => '0', 'training_minutes' => '']))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, BodyProfile::query()->count());
        $profile = $user->bodyProfile()->firstOrFail();
        $this->assertSame(2000, $profile->kcal_override);
        $this->assertSame(0, $profile->training_days);
        $this->assertSame(60, $profile->training_minutes);
    }

    public function test_everyone_only_sees_their_own_profile(): void
    {
        $other = BodyProfile::factory()->create(['weight_kg' => 99.5]);
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $response = $this->get(route('body-profile.edit'))->assertOk();

        $this->assertNull($response->viewData('budget'));
        $this->assertFalse($response->viewData('profile')->exists);
        $response->assertDontSee('99,5');

        $this->put(route('body-profile.update'), $this->validData());
        $this->assertSame(99.5, $other->fresh()->weight_kg);
    }

    public function test_validation(): void
    {
        $this->actingAs(User::factory()->create());

        $this->put(route('body-profile.update'), $this->validData([
            'birth_date' => '2010-01-01',
            'height_cm' => '90',
            'goal' => 'bulk',
            'training_minutes' => '',
            'kcal_override' => '500',
        ]))->assertSessionHasErrors(['birth_date', 'height_cm', 'goal', 'training_minutes', 'kcal_override']);

        $this->assertSame(0, BodyProfile::query()->count());
    }
}
