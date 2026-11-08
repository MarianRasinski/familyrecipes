<?php

namespace App\Actions\Training;

use App\Models\Exercise;
use App\Models\Workout;
use App\Models\WorkoutExercise;

/**
 * Hängt eine Übung mit ihrer Standardvorgabe aus dem Katalog an das Ende einer Einheit an.
 */
class AddExerciseToWorkout
{
    public function handle(Workout $workout, Exercise $exercise): WorkoutExercise
    {
        return $workout->exercises()->create([
            'exercise_id' => $exercise->id,
            'position' => (int) $workout->exercises()->max('position') + 1,
            'sets' => $exercise->default_sets ?? (int) config('training.default_sets'),
            'target' => $exercise->target,
            'rest_seconds' => (int) config('training.default_rest_seconds'),
        ]);
    }
}
