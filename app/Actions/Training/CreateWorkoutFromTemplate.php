<?php

namespace App\Actions\Training;

use App\Enums\ExerciseLevel;
use App\Models\Exercise;
use App\Models\ExerciseMuscleGroup;
use App\Models\MuscleGroup;
use App\Models\User;
use App\Models\Workout;
use Illuminate\Support\Facades\DB;

/**
 * Vorlage „Ganzkörper“: wählt Übungen bis zur gewählten Stufe, nur mit vorhandenen Geräten, so dass möglichst
 * viele verschiedene Hauptmuskelgruppen vorkommen (höchstens `training.template_max_exercises`).
 */
class CreateWorkoutFromTemplate
{
    private const LEVEL_ORDER = ['beginner' => 1, 'intermediate' => 2, 'advanced' => 3];

    public function __construct(private AddExerciseToWorkout $addExercise) {}

    public function handle(User $user, ExerciseLevel $level): ?Workout
    {
        $maxRank = self::LEVEL_ORDER[$level->value];

        // Passende Stufe zuerst (dann leichtere), innerhalb einer Stufe in Katalog-Reihenfolge
        $candidates = Exercise::query()
            ->doableAtHome()
            ->with('muscleGroups')
            ->orderBy('id')
            ->get()
            ->filter(fn (Exercise $exercise) => (self::LEVEL_ORDER[$exercise->level?->value] ?? 1) <= $maxRank)
            ->sortBy(fn (Exercise $exercise) => [$maxRank - (self::LEVEL_ORDER[$exercise->level?->value] ?? 1), $exercise->id])
            ->values();

        if ($candidates->isEmpty()) {
            return null;
        }

        $max = (int) config('training.template_max_exercises');
        $covered = collect();
        $chosen = collect();

        // 1. Runde: jede Übung muss mindestens eine neue Hauptmuskelgruppe bringen
        foreach ($candidates as $exercise) {
            $primary = $exercise->muscleGroups
                ->filter(fn (MuscleGroup $group) => $group->pivot instanceof ExerciseMuscleGroup && $group->pivot->is_primary)
                ->pluck('id');

            if ($chosen->count() < $max && $primary->diff($covered)->isNotEmpty()) {
                $chosen->push($exercise);
                $covered = $covered->merge($primary);
            }
        }

        // 2. Runde: mit weiteren Übungen auffüllen, falls Platz ist
        foreach ($candidates as $exercise) {
            if ($chosen->count() >= min($max, 6)) {
                break;
            }
            if (! $chosen->contains($exercise)) {
                $chosen->push($exercise);
            }
        }

        return DB::transaction(function () use ($user, $level, $chosen) {
            $workout = $user->workouts()->create([
                'name' => 'Ganzkörper ('.$level->label().')',
                'notes' => 'Aus der Vorlage erstellt – Übungen, Sätze und Pausen nach Belieben anpassen.',
            ]);

            $chosen->each(fn (Exercise $exercise) => $this->addExercise->handle($workout, $exercise));

            return $workout;
        });
    }
}
