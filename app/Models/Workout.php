<?php

namespace App\Models;

use Database\Factories\WorkoutFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Eine Trainingseinheit einer Person, z. B. „Ganzkörper A“, mit Übungen in fester Reihenfolge.
 */
class Workout extends Model
{
    /** @use HasFactory<WorkoutFactory> */
    use HasFactory;

    /**
     * user_id wird nur über die Beziehung gesetzt.
     *
     * @var list<string>
     */
    protected $fillable = ['name', 'notes'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function exercises(): HasMany
    {
        return $this->hasMany(WorkoutExercise::class)->orderBy('position')->orderBy('id');
    }

    public function schedule(): HasMany
    {
        return $this->hasMany(ScheduledWorkout::class)->orderBy('weekday');
    }

    /**
     * Geschätzte Dauer in Minuten: je Satz ca. 45 s Arbeit (bzw. die Zielzeit) plus Pause.
     * Erwartet geladene `exercises`.
     */
    public function estimatedMinutes(): int
    {
        $seconds = $this->exercises->sum(fn (WorkoutExercise $item) => $item->estimatedSeconds());

        return (int) max(1, round($seconds / 60));
    }
}
