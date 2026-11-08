<?php

namespace Tests\Feature;

use App\Enums\ExerciseMeasure;
use App\Models\Exercise;
use App\Models\TrainingDay;
use App\Models\User;
use App\Models\Workout;
use App\Models\WorkoutExercise;
use App\Models\WorkoutSession;
use App\Support\Planning\WeekPlan;
use App\Support\Training\ExerciseHistory;
use App\Support\Week;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkoutLogTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Workout $workout;

    private Exercise $squat;

    private Exercise $plank;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->travelTo('2026-10-14 18:00'); // Mittwoch, KW 42

        $this->user = User::factory()->create();
        $this->workout = Workout::factory()->for($this->user)->create(['name' => 'Ganzkörper A']);
        $this->squat = Exercise::factory()->create(['name' => 'Goblet Squat', 'measure' => ExerciseMeasure::Reps]);
        $this->plank = Exercise::factory()->create(['name' => 'Plank', 'measure' => ExerciseMeasure::Duration]);
        WorkoutExercise::factory()->for($this->workout)->create(['exercise_id' => $this->squat->id, 'position' => 1, 'sets' => 3, 'target' => '10-12']);
        WorkoutExercise::factory()->for($this->workout)->create(['exercise_id' => $this->plank->id, 'position' => 2, 'sets' => 2, 'target' => '30-45 Sekunden']);
        $this->actingAs($this->user);
    }

    private function start(): WorkoutSession
    {
        $this->post(route('workout-sessions.store', $this->workout))->assertRedirect();

        return $this->user->workoutSessions()->latest('id')->firstOrFail();
    }

    public function test_starting_creates_planned_sets(): void
    {
        $session = $this->start();

        $this->assertSame('Ganzkörper A', $session->name);
        $this->assertSame('2026-10-14', $session->date->toDateString());
        $this->assertFalse($session->isFinished());
        $this->assertSame(5, $session->sets()->count());
        $this->assertSame([1, 2, 3], $session->sets()->where('exercise_id', $this->squat->id)->pluck('set_number')->all());

        // Erneut starten am selben Tag → weiter im laufenden Training
        $this->post(route('workout-sessions.store', $this->workout))->assertRedirect(route('workout-sessions.show', $session));
        $this->assertSame(1, WorkoutSession::query()->count());

        $this->get(route('workout-sessions.show', $session))
            ->assertOk()
            ->assertSee('Goblet Squat')
            ->assertSee('Ziel: 3 × 10-12')
            ->assertSee('name="sets['.$session->sets()->where('exercise_id', $this->plank->id)->value('id').'][seconds]"', false);
    }

    public function test_logging_sets_and_finishing(): void
    {
        $session = $this->start();
        $sets = $session->sets()->get();
        $squats = $sets->where('exercise_id', $this->squat->id)->values();
        $planks = $sets->where('exercise_id', $this->plank->id)->values();

        // Zwischenspeichern
        $this->put(route('workout-sessions.update', $session), [
            'sets' => [
                $squats[0]->id => ['reps' => '12', 'weight_kg' => '16', 'done' => '1'],
                $squats[1]->id => ['reps' => '10', 'weight_kg' => '16,5', 'done' => '1'],
                $planks[0]->id => ['seconds' => '40', 'done' => '1'],
            ],
        ])->assertRedirect(route('workout-sessions.show', $session));

        $session->refresh()->load('sets');
        $this->assertFalse($session->isFinished());
        $this->assertSame(3, $session->doneCount());
        $this->assertSame(16.5, $squats[1]->fresh()->weight_kg);
        $this->assertSame(12 * 16 + 10 * 16.5, $session->volumeKg());
        $this->assertSame(0, TrainingDay::query()->count());

        // Abschließen → Mittwoch (nicht im Wochenplan) wird Trainingstag
        $this->put(route('workout-sessions.update', $session), [
            'sets' => [$squats[0]->id => ['reps' => '12', 'weight_kg' => '16', 'done' => '1']],
            'notes' => 'Gut gelaufen',
            'finish' => '1',
        ])->assertRedirect(route('workout-sessions.index'));

        $session->refresh();
        $this->assertTrue($session->isFinished());
        $this->assertSame('Gut gelaufen', $session->notes);
        $this->assertTrue(WeekPlan::for($this->user, Week::current())->days[3]->isTrainingDay);

        $this->get(route('workout-sessions.index'))->assertSee('Ganzkörper A')->assertSee('fertig');
    }

    public function test_next_session_prefills_weights_and_shows_last_time(): void
    {
        $first = $this->start();
        $squats = $first->sets()->where('exercise_id', $this->squat->id)->get();
        $this->put(route('workout-sessions.update', $first), [
            'sets' => [
                $squats[0]->id => ['reps' => '12', 'weight_kg' => '16', 'done' => '1'],
                $squats[1]->id => ['reps' => '10', 'weight_kg' => '20', 'done' => '1'],
            ],
            'finish' => '1',
        ]);

        $this->travelTo('2026-10-16 18:00');
        $second = $this->start();

        $weights = $second->sets()->where('exercise_id', $this->squat->id)->pluck('weight_kg')->all();
        // Satz 3 gab es letztes Mal nicht (nicht erledigt) → Gewicht des letzten erledigten Satzes
        $this->assertSame([16.0, 20.0, 20.0], $weights);

        $this->get(route('workout-sessions.show', $second))->assertSee('Letztes Mal (14.10.)')->assertSee('12 × 16 kg · 10 × 20 kg');

        $progress = ExerciseHistory::progress($this->user, $this->squat->id);
        $this->assertCount(1, $progress);
        $this->assertSame('10 × 20 kg', $progress[0]['best']->resultLabel());
        $this->get(route('exercises.show', $this->squat))->assertSee('Mein Verlauf')->assertSee('10 × 20 kg');
    }

    public function test_history_is_private(): void
    {
        $session = $this->start();
        $this->put(route('workout-sessions.update', $session), [
            'sets' => [$session->sets()->value('id') => ['reps' => '12', 'weight_kg' => '16', 'done' => '1']],
            'finish' => '1',
        ]);

        $other = User::factory()->admin()->create();
        $this->actingAs($other);

        $this->get(route('workout-sessions.show', $session))->assertForbidden();
        $this->put(route('workout-sessions.update', $session), ['finish' => '1'])->assertForbidden();
        $this->delete(route('workout-sessions.destroy', $session))->assertForbidden();
        $this->post(route('workout-sessions.store', $this->workout))->assertForbidden();
        $this->get(route('workout-sessions.index'))->assertDontSee('Ganzkörper A');
        $this->get(route('exercises.show', $this->squat))->assertDontSee('Mein Verlauf');
        $this->assertTrue(ExerciseHistory::progress($other, $this->squat->id)->isEmpty());
    }

    public function test_validation_and_delete(): void
    {
        $session = $this->start();
        $setId = $session->sets()->value('id');

        $this->put(route('workout-sessions.update', $session), ['sets' => [$setId => ['reps' => '-1', 'weight_kg' => 'viel']]])
            ->assertSessionHasErrors(["sets.{$setId}.reps", "sets.{$setId}.weight_kg"]);

        $this->delete(route('workout-sessions.destroy', $session))->assertRedirect(route('workout-sessions.index'));
        $this->assertModelMissing($session);
    }

    public function test_session_survives_workout_deletion(): void
    {
        $session = $this->start();

        $this->workout->delete();

        $this->assertNull($session->fresh()->workout_id);
        $this->get(route('workout-sessions.show', $session))->assertOk()->assertSee('Ganzkörper A');
    }

    public function test_sets_can_be_added_and_removed_during_training(): void
    {
        $session = $this->start();
        $squats = fn () => $session->sets()->where('exercise_id', $this->squat->id)->get();
        [$first, $second] = $squats();

        // Satz hinzufügen: Eingaben bleiben, neuer Satz übernimmt Gewicht und Ziel des letzten
        $this->put(route('workout-sessions.update', $session), [
            'sets' => [$squats()->last()->id => ['reps' => '10', 'weight_kg' => '18']],
            'add_set' => 1,
        ])->assertRedirect(route('workout-sessions.show', $session).'#uebung-1');

        $this->assertSame([1, 2, 3, 4], $squats()->pluck('set_number')->all());
        $this->assertSame(18.0, $squats()->last()->weight_kg);
        $this->assertSame('10-12', $squats()->last()->target);
        $this->assertNull($squats()->last()->reps);

        // Satz 2 entfernen → Nummern rücken nach
        $this->put(route('workout-sessions.update', $session), ['remove_set' => $second->id])
            ->assertRedirect(route('workout-sessions.show', $session).'#uebung-1');
        $this->assertModelMissing($second);
        $this->assertSame([1, 2, 3], $squats()->pluck('set_number')->all());
        $this->assertTrue($first->is($squats()->first()));

        // Fremde Sätze lassen sich nicht entfernen
        $foreign = WorkoutSession::factory()->create();
        $foreignSet = $foreign->sets()->create(['exercise_id' => $this->squat->id, 'position' => 1, 'set_number' => 1]);
        $this->put(route('workout-sessions.update', $session), ['remove_set' => $foreignSet->id])->assertSessionHasErrors('remove_set');
        $this->assertModelExists($foreignSet);
    }

    public function test_exercise_page_shows_progress_chart(): void
    {
        foreach (['2026-10-01' => 16, '2026-10-05' => 18, '2026-10-09' => 20] as $date => $weight) {
            $session = WorkoutSession::factory()->for($this->user)->finished()->create(['date' => $date]);
            $session->sets()->create(['exercise_id' => $this->squat->id, 'position' => 1, 'set_number' => 1, 'reps' => 10, 'weight_kg' => $weight, 'done' => true]);
        }

        $response = $this->get(route('exercises.show', $this->squat))->assertOk();

        $chart = $response->viewData('chart');
        $this->assertSame('Gewicht im besten Satz', $chart['title']);
        $this->assertSame([16.0, 18.0, 20.0], array_column($chart['points'], 'value'));
        $this->assertSame(['01.10.', '05.10.', '09.10.'], array_column($chart['points'], 'label'));
        $response->assertSee('aria-label="Gewicht im besten Satz: von 16 auf 20 kg"', false)
            ->assertSee('<title>09.10.: 10 × 20 kg</title>', false);

        // Nur ein Training → kein Diagramm, nur die Liste
        $this->assertNull(ExerciseHistory::chart(ExerciseHistory::progress($this->user, $this->plank->id)));
    }

    public function test_dashboard_offers_start_and_shows_done(): void
    {
        $this->user->scheduledWorkouts()->create(['weekday' => 3, 'workout_id' => $this->workout->id]);

        $this->get('/')->assertSee('▶ Starten')->assertSee(route('workout-sessions.store', $this->workout), false);

        $session = $this->start();
        $this->get('/')->assertSee('Weiter');

        $this->put(route('workout-sessions.update', $session), ['finish' => '1']);
        $this->get('/')->assertSee('✓ erledigt');
    }
}
