<?php

namespace App\Models;

use App\Enums\ExerciseMeasure;
use App\Support\Training\ExerciseTarget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ein Satz im Trainingsprotokoll: Vorgabe beim Start und was tatsächlich geschafft wurde.
 */
class WorkoutSet extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = ['exercise_id', 'position', 'set_number', 'target', 'reps', 'seconds', 'weight_kg', 'done'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'set_number' => 'integer',
            'reps' => 'integer',
            'seconds' => 'integer',
            'weight_kg' => 'float',
            'done' => 'boolean',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(WorkoutSession::class, 'workout_session_id');
    }

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }

    /**
     * Wird dieser Satz in Sekunden gemessen (laut Vorgabe bzw. Übung)? Erwartet geladene `exercise`.
     */
    public function isTimed(): bool
    {
        $measure = ExerciseTarget::parse($this->target)->measure ?? $this->exercise?->measure;

        return $measure === ExerciseMeasure::Duration;
    }

    /**
     * „12 × 16 kg“, „45 s“, „12 Wdh.“ oder „–“.
     */
    public function resultLabel(): string
    {
        $amount = match (true) {
            $this->seconds !== null => $this->seconds.' s',
            $this->reps !== null => $this->reps.($this->weight_kg ? '' : ' Wdh.'),
            default => null,
        };

        if ($this->weight_kg) {
            $weight = rtrim(rtrim(number_format($this->weight_kg, 1, ',', ''), '0'), ',').' kg';

            return $amount ? "{$amount} × {$weight}" : $weight;
        }

        return $amount ?? '–';
    }
}
