<?php

namespace App\Actions\Training;

use App\Actions\ToggleTrainingDay;
use App\Models\WorkoutSession;
use Illuminate\Support\Facades\DB;

/**
 * Speichert die eingetragenen Sätze eines Trainings; beim Abschließen zählt der Tag als Trainingstag
 * (auch wenn er im Wochenplan nicht vorgesehen war).
 */
class SaveWorkoutSession
{
    public function __construct(private ToggleTrainingDay $toggleTrainingDay) {}

    /**
     * @param  array<int|string, array{reps?: int|string|null, seconds?: int|string|null, weight_kg?: float|string|null, done?: bool|string|null}>  $sets
     */
    public function handle(WorkoutSession $session, array $sets, ?string $notes, bool $finish): void
    {
        DB::transaction(function () use ($session, $sets, $notes, $finish) {
            foreach ($session->sets()->get() as $set) {
                $row = $sets[$set->id] ?? [];

                $set->fill([
                    'reps' => filled($row['reps'] ?? null) ? (int) $row['reps'] : null,
                    'seconds' => filled($row['seconds'] ?? null) ? (int) $row['seconds'] : null,
                    'weight_kg' => filled($row['weight_kg'] ?? null) ? round((float) $row['weight_kg'], 1) : null,
                    'done' => (bool) ($row['done'] ?? false),
                ])->save();
            }

            $session->fill([
                'notes' => filled($notes) ? trim($notes) : null,
                'finished_at' => $finish ? ($session->finished_at ?? now()) : $session->finished_at,
            ])->save();
        });

        if ($finish) {
            $this->toggleTrainingDay->handle($session->loadMissing('user')->user, $session->date->toImmutable(), true);
        }
    }
}
