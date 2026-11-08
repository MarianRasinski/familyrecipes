<?php

namespace App\Models;

use Database\Factories\WorkoutSessionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * Ein durchgeführtes (oder laufendes) Training mit den tatsächlichen Sätzen.
 */
class WorkoutSession extends Model
{
    /** @use HasFactory<WorkoutSessionFactory> */
    use HasFactory;

    /**
     * user_id wird nur über die Beziehung gesetzt.
     *
     * @var list<string>
     */
    protected $fillable = ['workout_id', 'name', 'date', 'finished_at', 'notes'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'finished_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function workout(): BelongsTo
    {
        return $this->belongsTo(Workout::class);
    }

    public function sets(): HasMany
    {
        return $this->hasMany(WorkoutSet::class)->orderBy('position')->orderBy('set_number');
    }

    public function isFinished(): bool
    {
        return $this->finished_at !== null;
    }

    /**
     * Sätze je Übung in Trainingsreihenfolge. Erwartet geladene `sets.exercise`.
     *
     * @return Collection<int, Collection<int, WorkoutSet>>
     */
    public function setsByExercise(): Collection
    {
        return $this->sets->groupBy('position')->values();
    }

    /**
     * Bewegtes Gewicht: Summe aus Wiederholungen × kg der erledigten Sätze. Erwartet geladene `sets`.
     */
    public function volumeKg(): float
    {
        return (float) $this->sets
            ->filter(fn (WorkoutSet $set) => $set->done && $set->reps && $set->weight_kg)
            ->sum(fn (WorkoutSet $set) => $set->reps * $set->weight_kg);
    }

    public function doneCount(): int
    {
        return $this->sets->where('done', true)->count();
    }
}
