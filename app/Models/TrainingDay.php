<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Abweichung vom Trainingsplan für einen einzelnen Tag: `is_training` = an diesem Tag trainieren (oder nicht),
 * unabhängig vom Wochenplan (`scheduled_workouts`). Ohne Zeile gilt der Wochenplan.
 */
class TrainingDay extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = ['date', 'is_training'];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = ['is_training' => true];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['date' => 'date', 'is_training' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
