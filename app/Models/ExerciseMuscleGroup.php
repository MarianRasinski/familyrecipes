<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Muskelgruppe einer Übung; `is_primary` = Haupt- statt Nebenmuskulatur.
 */
class ExerciseMuscleGroup extends Pivot
{
    protected $table = 'exercise_muscle_group';

    protected function casts(): array
    {
        return ['is_primary' => 'boolean'];
    }
}
