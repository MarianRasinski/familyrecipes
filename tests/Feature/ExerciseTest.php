<?php

namespace Tests\Feature;

use App\Enums\ExerciseLevel;
use App\Models\Equipment;
use App\Models\Exercise;
use App\Models\MuscleGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExerciseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->actingAs(User::factory()->child()->create()); // auch Kinder dürfen lesen
    }

    public function test_guests_are_redirected_to_login(): void
    {
        auth()->logout();

        $this->get(route('exercises.index'))->assertRedirect(route('login'));
    }

    public function test_index_lists_exercises_alphabetically(): void
    {
        Exercise::factory()->create(['name' => 'Plank']);
        Exercise::factory()->create(['name' => 'Goblet Squat']);

        $this->get(route('exercises.index'))
            ->assertOk()
            ->assertSeeInOrder(['Goblet Squat', 'Plank']);
    }

    public function test_index_filters_by_muscle_group_equipment_and_level(): void
    {
        $glutes = MuscleGroup::factory()->create(['name' => 'Gesäß']);
        $kettlebell = Equipment::factory()->create(['name' => 'Kettlebell']);

        $swing = Exercise::factory()->create(['name' => 'Kettlebell Swing', 'level' => ExerciseLevel::Advanced]);
        $swing->muscleGroups()->attach($glutes, ['is_primary' => true]);
        $swing->equipment()->attach($kettlebell);

        $bridge = Exercise::factory()->create(['name' => 'Glute Bridge', 'level' => ExerciseLevel::Beginner]);
        $bridge->muscleGroups()->attach($glutes, ['is_primary' => true]);

        Exercise::factory()->create(['name' => 'Bizepscurls', 'level' => ExerciseLevel::Beginner]);

        $names = fn (array $query) => $this->get(route('exercises.index', $query))->viewData('exercises')->pluck('name')->all();

        $this->assertSame(['Glute Bridge', 'Kettlebell Swing'], $names(['gruppe' => 'gesaess']));
        $this->assertSame(['Kettlebell Swing'], $names(['geraet' => 'kettlebell']));
        $this->assertSame(['Bizepscurls', 'Glute Bridge'], $names(['stufe' => 'beginner']));
        $this->assertSame(['Glute Bridge'], $names(['gruppe' => 'gesaess', 'stufe' => 'beginner']));
        $this->assertSame(['Glute Bridge'], $names(['q' => 'glute']));
    }

    public function test_index_can_show_only_exercises_doable_with_available_equipment(): void
    {
        $mat = Equipment::factory()->create(['name' => 'Yogamatte']);
        $bar = Equipment::factory()->unavailable()->create(['name' => 'Klimmzugstange']);

        Exercise::factory()->create(['name' => 'Plank'])->equipment()->attach($mat);
        Exercise::factory()->create(['name' => 'Klimmzug'])->equipment()->attach($bar);
        Exercise::factory()->create(['name' => 'Hängen optional'])->equipment()->attach($bar, ['is_optional' => true]);

        $names = $this->get(route('exercises.index', ['zuhause' => 1]))->viewData('exercises')->pluck('name')->all();

        $this->assertSame(['Hängen optional', 'Plank'], $names);
    }

    public function test_show_displays_prescription_equipment_and_muscle_groups(): void
    {
        $exercise = Exercise::factory()->create([
            'name' => 'Kurzhantel Rudern',
            'default_sets' => 3,
            'target' => '10-12 je Seite',
            'per_side' => true,
        ]);
        $exercise->equipment()->attach(Equipment::factory()->create(['name' => 'Kurzhanteln', 'variants' => ['verstellbar']]));
        $exercise->muscleGroups()->attach(MuscleGroup::factory()->create(['name' => 'Latissimus']), ['is_primary' => true]);
        $exercise->muscleGroups()->attach(MuscleGroup::factory()->create(['name' => 'Bizeps']), ['is_primary' => false]);

        $this->get(route('exercises.show', $exercise))
            ->assertOk()
            ->assertSee('3 × 10-12 je Seite')
            ->assertSee('Kurzhanteln')
            ->assertSee('vorhanden: verstellbar')
            ->assertSeeInOrder(['Hauptsächlich', 'Latissimus', 'Unterstützend', 'Bizeps']);
    }

    public function test_exercises_are_in_the_navigation(): void
    {
        $this->get(route('home'))->assertSee(route('exercises.index'));
    }
}
