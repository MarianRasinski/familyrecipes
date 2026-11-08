<?php

namespace App\Actions\Training;

use App\Models\Workout;
use App\Models\WorkoutExercise;
use App\Models\WorkoutSession;
use App\Support\Training\ExerciseHistory;
use Illuminate\Support\Facades\DB;

/**
 * Startet ein Training aus einer Einheit: legt je Übung die geplanten Sätze an und füllt das Gewicht
 * vom letzten Mal vor. Läuft heute schon ein (nicht abgeschlossenes) Training dieser Einheit, geht es dort weiter.
 */
class StartWorkoutSession
{
    public function handle(Workout $workout): WorkoutSession
    {
        $workout->loadMissing(['user', 'exercises']);
        $user = $workout->user;

        $running = $user->workoutSessions()
            ->where('workout_id', $workout->id)
            ->whereDate('date', today())
            ->whereNull('finished_at')
            ->first();

        if ($running) {
            return $running;
        }

        $last = ExerciseHistory::lastSets($user, $workout->exercises->pluck('exercise_id'));

        return DB::transaction(function () use ($user, $workout, $last) {
            $session = $user->workoutSessions()->create([
                'workout_id' => $workout->id,
                'name' => $workout->name,
                'date' => today()->toDateString(),
            ]);

            $workout->exercises->values()->each(function (WorkoutExercise $item, int $index) use ($session, $last) {
                $previous = $last->get($item->exercise_id, collect());

                foreach (range(1, max(1, $item->sets)) as $number) {
                    // Gewicht vom gleichen (sonst letzten) Satz des letzten Trainings übernehmen
                    $match = $previous->firstWhere('set_number', $number) ?? $previous->last();

                    $session->sets()->create([
                        'exercise_id' => $item->exercise_id,
                        'position' => $index + 1,
                        'set_number' => $number,
                        'target' => $item->target,
                        'weight_kg' => $match?->weight_kg,
                    ]);
                }
            });

            return $session;
        });
    }
}
