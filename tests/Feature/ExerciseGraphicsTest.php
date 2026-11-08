<?php

namespace Tests\Feature;

use App\Models\Exercise;
use App\Models\MuscleGroup;
use App\Models\User;
use App\Models\Workout;
use App\Models\WorkoutExercise;
use App\Support\Training\MuscleMap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Muskelkarte (vorne/hinten) und Übungsbilder (Strichfiguren Start → Ende).
 */
class ExerciseGraphicsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->actingAs(User::factory()->create());
    }

    private function exercise(string $slug, array $primary, array $secondary = []): Exercise
    {
        $exercise = Exercise::factory()->create(['name' => ucfirst($slug), 'slug' => $slug]);

        foreach ([true => $primary, false => $secondary] as $isPrimary => $slugs) {
            foreach ($slugs as $groupSlug) {
                $group = MuscleGroup::query()->firstWhere('slug', $groupSlug) ?? MuscleGroup::factory()->create(['name' => $groupSlug, 'slug' => $groupSlug]);
                $exercise->muscleGroups()->attach($group, ['is_primary' => (bool) $isPrimary]);
            }
        }

        return $exercise->load('muscleGroups');
    }

    public function test_muscle_groups_map_to_body_regions(): void
    {
        $map = MuscleMap::forExercise($this->exercise('goblet-squat', ['gesaess', 'quadrizeps'], ['bauch', 'ruecken']));

        $this->assertSame(['glutes', 'quads'], $map->primary);
        $this->assertSame(['abs', 'obliques', 'traps', 'upper_back', 'lats', 'lower_back'], $map->secondary);
        $this->assertSame('1', $map->style('glutes')['opacity']);
        $this->assertSame('0.4', $map->style('lats')['opacity']);
        $this->assertSame('var(--color-line)', $map->style('biceps')['fill']);
    }

    public function test_primary_wins_over_secondary_and_unknown_groups_are_ignored(): void
    {
        $press = $this->exercise('floor-press', ['brust'], ['vordere-schultern', 'gibt-es-nicht']);
        $raise = $this->exercise('front-raise', ['vordere-schultern']);

        $map = MuscleMap::forExercises(collect([$press, $raise]));

        $this->assertSame(['chest', 'front_delts'], $map->primary);
        $this->assertSame([], $map->secondary);
        $this->assertTrue(MuscleMap::forExercise($this->exercise('leer', []))->isEmpty());
    }

    public function test_every_muscle_group_of_the_catalog_has_regions(): void
    {
        $catalog = collect(json_decode(file_get_contents(database_path('data/exercises/ganzkoerper.json')), true)['uebungen']);
        $names = $catalog->flatMap(fn (array $exercise) => [...($exercise['muskelgruppen_haupt'] ?? []), ...($exercise['muskelgruppen_neben'] ?? [])]);

        $this->assertNotEmpty($names);
        foreach ($names->unique() as $name) {
            $slug = Str::slug($name, language: 'de');
            $this->assertArrayHasKey($slug, config('muscle_map.groups'), "Muskelgruppe „{$name}“ fehlt in config/muscle_map.php");
        }
    }

    public function test_every_catalog_exercise_has_an_illustration(): void
    {
        $catalog = collect(json_decode(file_get_contents(database_path('data/exercises/ganzkoerper.json')), true)['uebungen']);

        foreach ($catalog as $exercise) {
            $slug = Str::slug($exercise['name'], language: 'de');
            $frames = config("exercise_illustrations.{$slug}");

            $this->assertNotEmpty($frames, "Kein Übungsbild für „{$exercise['name']}“ ({$slug})");
            foreach ($frames as $frame) {
                foreach (['head', 's', 'h', 'k1', 'a1'] as $joint) {
                    $this->assertArrayHasKey($joint, $frame, "{$slug}: Gelenk {$joint} fehlt");
                }
            }
        }
    }

    public function test_exercise_page_shows_illustration_and_muscle_map(): void
    {
        $exercise = $this->exercise('goblet-squat', ['gesaess'], ['bauch']);

        $this->get(route('exercises.show', $exercise))
            ->assertOk()
            ->assertSee('aria-label="Goblet-squat, Start"', false)
            ->assertSee('aria-label="Goblet-squat, Ende"', false)
            ->assertSee('Beanspruchte Muskeln bei Goblet-squat')
            ->assertSee('<title>Gesäß</title>', false)
            ->assertSee('Hauptmuskulatur');
    }

    public function test_exercise_without_illustration_still_works(): void
    {
        $exercise = $this->exercise('ganz-neue-uebung', []);

        $this->get(route('exercises.show', $exercise))
            ->assertOk()
            ->assertDontSee('Ausführung:')
            ->assertDontSee('Hauptmuskulatur');
    }

    public function test_workout_page_shows_combined_muscle_map(): void
    {
        $user = User::factory()->create();
        $workout = Workout::factory()->for($user)->create(['name' => 'Ganzkörper A']);
        WorkoutExercise::factory()->for($workout)->create(['exercise_id' => $this->exercise('squat', ['quadrizeps'])->id]);
        WorkoutExercise::factory()->for($workout)->create(['exercise_id' => $this->exercise('row', ['latissimus'], ['bizeps'])->id]);

        $response = $this->actingAs($user)->get(route('workouts.edit', $workout))->assertOk();

        $this->assertSame(['quads', 'lats'], $response->viewData('muscleMap')->primary);
        $this->assertSame(['biceps'], $response->viewData('muscleMap')->secondary);
        $response->assertSee('Beanspruchte Muskeln in Ganzkörper A');
    }
}
