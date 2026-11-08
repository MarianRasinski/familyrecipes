<?php

namespace App\Actions\Training;

use App\Actions\ToggleTrainingDay;
use App\Models\WorkoutSession;
use Illuminate\Support\Facades\DB;

/**
 * Speichert die eingetragenen Sätze eines Trainings; beim Abschließen zählt der Tag als Trainingstag
 * (auch wenn er im Wochenplan nicht vorgesehen war). Optional wird dabei ein Satz zu einer Übung hinzugefügt
 * (Kopie des letzten Satzes dieser Übung) oder ein Satz entfernt.
 */
class SaveWorkoutSession
{
    public function __construct(private ToggleTrainingDay $toggleTrainingDay) {}

    /**
     * @param  array<int|string, array{reps?: int|string|null, seconds?: int|string|null, weight_kg?: float|string|null, done?: bool|string|null}>  $sets
     */
    public function handle(WorkoutSession $session, array $sets, ?string $notes, bool $finish, ?int $addSetAt = null, ?int $removeSet = null): void
    {
        DB::transaction(function () use ($session, $sets, $notes, $finish, $addSetAt, $removeSet) {
            foreach ($session->sets()->get() as $set) {
                $row = $sets[$set->id] ?? [];

                $set->fill([
                    'reps' => filled($row['reps'] ?? null) ? (int) $row['reps'] : null,
                    'seconds' => filled($row['seconds'] ?? null) ? (int) $row['seconds'] : null,
                    'weight_kg' => filled($row['weight_kg'] ?? null) ? round((float) $row['weight_kg'], 1) : null,
                    'done' => (bool) ($row['done'] ?? false),
                ])->save();
            }

            if ($removeSet !== null) {
                $removed = $session->sets()->whereKey($removeSet)->first();
                $removed?->delete();

                // Satznummern der Übung lückenlos halten
                if ($removed) {
                    $session->sets()->where('position', $removed->position)->orderBy('set_number')->get()
                        ->each(fn ($set, int $i) => $set->update(['set_number' => $i + 1]));
                }
            }

            if ($addSetAt !== null) {
                $last = $session->sets()->where('position', $addSetAt)->reorder()->orderByDesc('set_number')->first();

                $last && $session->sets()->create([
                    'exercise_id' => $last->exercise_id,
                    'position' => $last->position,
                    'set_number' => $last->set_number + 1,
                    'target' => $last->target,
                    'weight_kg' => $last->weight_kg,
                ]);
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
