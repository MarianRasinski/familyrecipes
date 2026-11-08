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
     * Daten fürs Verlaufsdiagramm (älteste zuerst): bestes Gewicht – oder Wiederholungen bzw. Sekunden,
     * wenn ohne Gewicht trainiert wurde. null bei weniger als zwei Trainings.
     *
     * @param  Collection<int, array{date: CarbonInterface, best: WorkoutSet, sets: int}>  $progress  aus progress()
     * @return array{title: string, unit: string, points: list<array{label: string, value: float, text: string}>}|null
     */
    public static function chart(Collection $progress): ?array
    {
        if ($progress->count() < 2) {
            return null;
        }

        $best = $progress->pluck('best');
        [$field, $title, $unit] = match (true) {
            $best->contains(fn (WorkoutSet $set) => $set->weight_kg > 0) => ['weight_kg', 'Gewicht im besten Satz', 'kg'],
            $best->contains(fn (WorkoutSet $set) => $set->seconds > 0) => ['seconds', 'Dauer im besten Satz', 's'],
            default => ['reps', 'Wiederholungen im besten Satz', 'Wdh.'],
        };

        return [
            'title' => $title,
            'unit' => $unit,
            'points' => $progress->reverse()->values()->map(fn (array $entry) => [
                'label' => $entry['date']->format('d.m.'),
                'value' => (float) ($entry['best']->{$field} ?? 0),
                'text' => $entry['best']->resultLabel(),
            ])->all(),
        ];
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
