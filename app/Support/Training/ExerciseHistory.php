<?php

namespace App\Support\Training;

use App\Models\User;
use App\Models\WorkoutSet;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Was eine Person bei einer Übung zuletzt geschafft hat (nur erledigte Sätze aus abgeschlossenen Trainings).
 */
final class ExerciseHistory
{
    /**
     * Sätze des letzten abgeschlossenen Trainings je Übung.
     *
     * @param  iterable<int>  $exerciseIds
     * @return Collection<int, Collection<int, WorkoutSet>> exercise_id → Sätze (nach Satznummer)
     */
    public static function lastSets(User $user, iterable $exerciseIds, ?int $exceptSessionId = null): Collection
    {
        return self::doneSets($user, $exerciseIds, $exceptSessionId)
            ->groupBy('exercise_id')
            ->map(function (Collection $sets) {
                $latest = $sets->first()->workout_session_id;

                return $sets->where('workout_session_id', $latest)->sortBy('set_number')->values();
            });
    }

    /**
     * Verlauf einer Übung: je Training der beste Satz (höchstes Gewicht, dann meiste Wiederholungen/Sekunden).
     *
     * @return Collection<int, array{date: CarbonInterface, best: WorkoutSet, sets: int}>
     */
    public static function progress(User $user, int $exerciseId, int $limit = 10): Collection
    {
        return self::doneSets($user, [$exerciseId])
            ->groupBy('workout_session_id')
            ->take($limit)
            ->map(fn (Collection $sets) => [
                'date' => $sets->first()->session->date,
                'best' => $sets->sortByDesc(fn (WorkoutSet $set) => [(float) $set->weight_kg, (int) $set->reps, (int) $set->seconds])->first(),
                'sets' => $sets->count(),
            ])
            ->values();
    }

    /**
     * @param  iterable<int>  $exerciseIds
     * @return Collection<int, WorkoutSet> neueste Trainings zuerst
     */
    private static function doneSets(User $user, iterable $exerciseIds, ?int $exceptSessionId = null): Collection
    {
        $ids = collect($exerciseIds)->unique()->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return WorkoutSet::query()
            ->select('workout_sets.*')
            ->join('workout_sessions', 'workout_sessions.id', '=', 'workout_sets.workout_session_id')
            ->where('workout_sessions.user_id', $user->id)
            ->whereNotNull('workout_sessions.finished_at')
            ->when($exceptSessionId, fn ($query) => $query->where('workout_sessions.id', '!=', $exceptSessionId))
            ->whereIn('workout_sets.exercise_id', $ids)
            ->where('workout_sets.done', true)
            ->with('session')
            ->orderByDesc('workout_sessions.date')
            ->orderByDesc('workout_sessions.id')
            ->orderBy('workout_sets.set_number')
            ->get();
    }
}
