<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Gerät einer Übung; `is_optional` = Übung geht auch ohne.
 */
class EquipmentExercise extends Pivot
{
    protected $table = 'equipment_exercise';

    protected function casts(): array
    {
        return ['is_optional' => 'boolean'];
    }
}
