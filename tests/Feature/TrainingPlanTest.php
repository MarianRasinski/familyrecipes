<?php

namespace Tests\Feature;

use App\Enums\ExerciseLevel;
use App\Models\BodyProfile;
use App\Models\Equipment;
use App\Models\Exercise;
use App\Models\MuscleGroup;
use App\Models\TrainingDay;
use App\Models\User;
use App\Models\Workout;
use App\Models\WorkoutExercise;
use App\Support\Planning\WeekPlan;
use App\Support\Week;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrainingPlanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->travelTo('2026-10-14 18:00'); // Mittwoch, KW 42
    }

    public function test_training_page_lists_own_workouts_only(): void
    {
        $user = User::factory()->child()->create();
        Workout::factory()->for($user)->create(['name' => 'Meine Einheit']);
        Workout::factory()->create(['name' => 'Fremde Einheit']);

        $this->actingAs($user)->get(route('training.index'))
            ->assertOk()
            ->assertSee('Meine Einheit')
            ->assertDontSee('Fremde Einheit')
            ->assertSee('Vorlage: Ganzkörper');
    }

    public function test_create_workout_add_exercise_with_catalog_defaults(): void
    {
        $user = User::factory()->create();
        $exercise = Exercise::factory()->create(['name' => 'Goblet Squat', 'default_sets' => 4, 'target' => '10-15']);
        $this->actingAs($user);

        $this->post(route('workouts.store'), ['name' => 'Beine'])->assertRedirect();
        $workout = $user->workouts()->firstOrFail();

        $this->get(route('workouts.edit', $workout))->assertOk()
            ->assertViewHas('catalog', fn ($catalog) => $catalog->contains('id', $exercise->id));

        $this->post(route('workouts.exercises.store', $workout), ['exercise_id' => $exercise->id])
            ->assertRedirect();

        $item = $workout->exercises()->firstOrFail();
        $this->assertSame(4, $item->sets);
        $this->assertSame('10-15', $item->target);
        $this->assertSame(60, $item->rest_seconds);
        $this->assertSame(1, $item->position);

        $this->get(route('workouts.edit', $workout))->assertSee('Goblet Squat');
    }

    public function test_save_move_and_remove_exercises(): void
    {
        $user = User::factory()->create();
        $workout = Workout::factory()->for($user)->create();
        [$a, $b, $c] = collect([1, 2, 3])->map(fn ($position) => WorkoutExercise::factory()->for($workout)->create(['position' => $position]));
        $this->actingAs($user);

        $rows = fn (array $overrides = []) => collect([$a, $b, $c])->mapWithKeys(fn ($item) => [
            $item->id => ['sets' => 3, 'target' => '10-12', 'rest_seconds' => 60, 'notes' => ''],
        ])->replace($overrides)->all();

        // Speichern übernimmt Werte
        $this->put(route('workouts.update', $workout), [
            'name' => 'Ganzkörper A',
            'exercises' => $rows([$b->id => ['sets' => 5, 'target' => '45 Sekunden', 'rest_seconds' => 90, 'notes' => '16 kg']]),
        ])->assertRedirect(route('workouts.edit', $workout));

        $b->refresh();
        $this->assertSame([5, '45 Sekunden', 90, '16 kg'], [$b->sets, $b->target, $b->rest_seconds, $b->notes]);
        $this->assertSame('Ganzkörper A', $workout->fresh()->name);

        // C nach oben → A, C, B; Eingaben werden dabei mitgespeichert
        $this->put(route('workouts.update', $workout), [
            'name' => 'Ganzkörper A',
            'exercises' => $rows([$a->id => ['sets' => 2, 'target' => '8', 'rest_seconds' => '', 'notes' => '']]),
            'move' => "{$c->id}:up",
        ]);
        $this->assertSame([$a->id, $c->id, $b->id], $workout->exercises()->pluck('id')->all());
        $this->assertSame(2, $a->fresh()->sets);
        $this->assertNull($a->fresh()->rest_seconds);

        // A entfernen → C, B mit Positionen 1, 2
        $this->put(route('workouts.update', $workout), ['name' => 'Ganzkörper A', 'exercises' => $rows(), 'remove' => $a->id]);
        $this->assertModelMissing($a);
        $this->assertSame([1, 2], $workout->exercises()->pluck('position')->all());
        $this->assertSame([$c->id, $b->id], $workout->exercises()->pluck('id')->all());
    }

    public function test_validation_and_foreign_exercise_ids(): void
    {
        $user = User::factory()->create();
        $workout = Workout::factory()->for($user)->create();
        $foreign = WorkoutExercise::factory()->create();
        $this->actingAs($user);

        $this->put(route('workouts.update', $workout), ['name' => '', 'remove' => $foreign->id, 'move' => 'kaputt'])
            ->assertSessionHasErrors(['name', 'remove', 'move']);
        $this->assertModelExists($foreign);
    }

    public function test_others_cannot_see_or_change_a_workout(): void
    {
        $workout = Workout::factory()->create();
        $this->actingAs(User::factory()->admin()->create());

        $this->get(route('workouts.edit', $workout))->assertForbidden();
        $this->put(route('workouts.update', $workout), ['name' => 'Gekapert'])->assertForbidden();
        $this->post(route('workouts.exercises.store', $workout), ['exercise_id' => Exercise::factory()->create()->id])->assertForbidden();
        $this->delete(route('workouts.destroy', $workout))->assertForbidden();
        $this->assertModelExists($workout);
    }

    public function test_schedule_makes_days_training_days_in_my_plan(): void
    {
        $user = User::factory()->create();
        BodyProfile::factory()->for($user)->create(['training_days' => 2]);
        $workout = Workout::factory()->for($user)->create(['name' => 'Ganzkörper A']);
        $foreignWorkout = Workout::factory()->create();
        $this->actingAs($user);

        $this->put(route('training.schedule'), ['days' => [1 => $foreignWorkout->id]])->assertSessionHasErrors('days.1');

        $this->put(route('training.schedule'), ['days' => [1 => $workout->id, 2 => '', 4 => $workout->id]])
            ->assertRedirect(route('training.index'));
        $this->assertSame([1, 4], $user->scheduledWorkouts()->orderBy('weekday')->pluck('weekday')->all());

        $plan = WeekPlan::for($user, Week::current());
        $this->assertTrue($plan->days[1]->isTrainingDay);
        $this->assertTrue($workout->is($plan->days[1]->workout));
        $this->assertFalse($plan->days[2]->isTrainingDay);
        $this->assertSame((float) $plan->budget->trainingDayKcal(), $plan->days[4]->target->kcal);

        $this->get(route('personal-plan.show'))->assertSee('Ganzkörper A');
    }

    public function test_single_days_can_deviate_from_the_schedule(): void
    {
        $user = User::factory()->create();
        BodyProfile::factory()->for($user)->create(['training_days' => 1]);
        $workout = Workout::factory()->for($user)->create();
        $user->scheduledWorkouts()->create(['weekday' => 1, 'workout_id' => $workout->id]);
        $this->actingAs($user);

        // Montag (geplant) ausfallen lassen → Abweichung gespeichert
        $this->put(route('personal-plan.training', '2026-10-12'), ['training' => 0]);
        $this->assertFalse(TrainingDay::query()->sole()->is_training);
        $monday = WeekPlan::for($user, Week::current())->days[1];
        $this->assertFalse($monday->isTrainingDay);
        $this->assertNull($monday->workout);

        // Zurück auf Training = wie geplant → keine Abweichung mehr
        $this->put(route('personal-plan.training', '2026-10-12'), ['training' => 1]);
        $this->assertSame(0, TrainingDay::query()->count());

        // Andere Woche: Montag ist wieder automatisch Trainingstag
        $this->assertTrue(WeekPlan::for($user, Week::current()->next())->days[1]->isTrainingDay);
    }

    public function test_dashboard_shows_todays_workout(): void
    {
        $user = User::factory()->create();
        $workout = Workout::factory()->for($user)->create(['name' => 'Oberkörper']);
        $user->scheduledWorkouts()->create(['weekday' => 3, 'workout_id' => $workout->id]); // Mittwoch = heute

        $this->actingAs($user)->get('/')->assertSee('Heute trainieren')->assertSee('Oberkörper');
    }

    public function test_template_picks_doable_exercises_covering_muscle_groups(): void
    {
        $user = User::factory()->create();
        $legs = MuscleGroup::factory()->create(['name' => 'Beine']);
        $chest = MuscleGroup::factory()->create(['name' => 'Brust']);
        $missingGear = Equipment::factory()->create(['name' => 'Langhantel', 'is_available' => false]);

        $squat = Exercise::factory()->create(['name' => 'Squat', 'level' => ExerciseLevel::Beginner, 'default_sets' => 3, 'target' => '12']);
        $squat->muscleGroups()->attach($legs, ['is_primary' => true]);
        $lunge = Exercise::factory()->create(['name' => 'Ausfallschritt', 'level' => ExerciseLevel::Beginner]);
        $lunge->muscleGroups()->attach($legs, ['is_primary' => true]);
        $pushup = Exercise::factory()->create(['name' => 'Liegestütz', 'level' => ExerciseLevel::Beginner]);
        $pushup->muscleGroups()->attach($chest, ['is_primary' => true]);
        $bench = Exercise::factory()->create(['name' => 'Bankdrücken', 'level' => ExerciseLevel::Beginner]);
        $bench->muscleGroups()->attach($chest, ['is_primary' => true]);
        $bench->equipment()->attach($missingGear, ['is_optional' => false]);
        $hard = Exercise::factory()->create(['name' => 'Pistol Squat', 'level' => ExerciseLevel::Advanced]);
        $hard->muscleGroups()->attach($legs, ['is_primary' => true]);

        $this->actingAs($user)->post(route('training.template'), ['level' => 'beginner'])->assertRedirect();

        $workout = $user->workouts()->with('exercises.exercise')->sole();
        $names = $workout->exercises->pluck('exercise.name')->all();

        $this->assertSame('Ganzkörper (Anfänger)', $workout->name);
        // Erst je eine Übung pro Muskelgruppe, dann aufgefüllt; ohne fehlende Geräte und zu schwere Übungen
        $this->assertSame(['Squat', 'Liegestütz', 'Ausfallschritt'], $names);
        $this->assertSame(['3 × 12'], [$workout->exercises->first()->prescription()]);
    }

    public function test_estimated_duration(): void
    {
        $workout = Workout::factory()->create();
        WorkoutExercise::factory()->for($workout)->create(['sets' => 3, 'target' => '10-12', 'rest_seconds' => 60]);       // 3 × (36 + 60)
        WorkoutExercise::factory()->for($workout)->create(['sets' => 2, 'target' => '30 Sekunden je Seite', 'rest_seconds' => 30]); // 2 × (60 + 30)

        $this->assertSame(8, $workout->load('exercises')->estimatedMinutes()); // 468 s
    }

    public function test_delete_workout_removes_it_from_schedule(): void
    {
        $user = User::factory()->create();
        $workout = Workout::factory()->for($user)->create();
        $user->scheduledWorkouts()->create(['weekday' => 1, 'workout_id' => $workout->id]);

        $this->actingAs($user)->delete(route('workouts.destroy', $workout))->assertRedirect(route('training.index'));

        $this->assertModelMissing($workout);
        $this->assertSame(0, $user->scheduledWorkouts()->count());
    }
}
