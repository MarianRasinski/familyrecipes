<?php

namespace App\Models;

use App\Enums\ExerciseMeasure;
use App\Support\Training\ExerciseTarget;
use Database\Factories\WorkoutExerciseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Eine Übung innerhalb einer Trainingseinheit: Sätze × Ziel, Pause.
 */
class WorkoutExercise extends Model
{
    /** @use HasFactory<WorkoutExerciseFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = ['exercise_id', 'position', 'sets', 'target', 'rest_seconds', 'notes'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'sets' => 'integer',
            'rest_seconds' => 'integer',
        ];
    }

    public function workout(): BelongsTo
    {
        return $this->belongsTo(Workout::class);
    }

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }

    /**
     * „3 × 10-12“ bzw. „3 Sätze“.
     */
    public function prescription(): string
    {
        return filled($this->target) ? "{$this->sets} × {$this->target}" : "{$this->sets} Sätze";
    }

    /**
     * Arbeitszeit + Pausen; Wiederholungen ≈ 3 s je Wiederholung (mind. 30 s je Satz).
     */
    public function estimatedSeconds(): int
    {
        $target = ExerciseTarget::parse($this->target);
        $work = match ($target->measure) {
            ExerciseMeasure::Duration => $target->max ?? 45,
            ExerciseMeasure::Reps => max(30, ($target->max ?? 12) * 3),
            default => 60,
        };

        if ($target->perSide) {
            $work *= 2;
        }

        return $this->sets * ($work + ($this->rest_seconds ?? (int) config('training.default_rest_seconds')));
    }
}
