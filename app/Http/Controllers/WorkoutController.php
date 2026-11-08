<?php

namespace App\Http\Controllers;

use App\Actions\Training\AddExerciseToWorkout;
use App\Actions\Training\SaveWorkout;
use App\Http\Requests\UpdateWorkoutRequest;
use App\Models\Exercise;
use App\Models\Workout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Trainingseinheiten anlegen, bearbeiten (Übungen, Sätze, Pausen) und löschen – nur eigene (WorkoutPolicy).
 */
class WorkoutController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate(['name' => ['required', 'string', 'max:100']]);

        $workout = $request->user()->workouts()->create(['name' => $validated['name']]);

        return to_route('workouts.edit', $workout);
    }

    public function edit(Workout $workout): View
    {
        Gate::authorize('update', $workout);

        $workout->load(['exercises.exercise', 'schedule']);

        return view('training.workout', [
            'workout' => $workout,
            // Für die Übungsauswahl (recipePicker erwartet id/title/emoji/favorite)
            'catalog' => Exercise::query()
                ->with('equipment')
                ->orderBy('name')
                ->get()
                ->map(fn (Exercise $exercise) => [
                    'id' => $exercise->id,
                    'title' => $exercise->name.($exercise->prescription() ? ' · '.$exercise->prescription() : ''),
                    'emoji' => $exercise->isDoableAtHome() ? '🏠' : '🏋️',
                    'favorite' => false,
                ]),
        ]);
    }

    public function update(UpdateWorkoutRequest $request, Workout $workout, SaveWorkout $save): RedirectResponse
    {
        $save->handle(
            $workout,
            $request->safe()->only(['name', 'notes', 'exercises']),
            $request->filled('remove') ? (int) $request->validated('remove') : null,
            $request->validated('move'),
        );

        $message = match (true) {
            $request->filled('remove') => 'Übung entfernt.',
            $request->filled('move') => null,
            default => 'Einheit gespeichert.',
        };

        return to_route('workouts.edit', $workout)->with('status', $message);
    }

    public function addExercise(Request $request, Workout $workout, AddExerciseToWorkout $add): RedirectResponse
    {
        Gate::authorize('update', $workout);

        $validated = $request->validate(['exercise_id' => ['required', 'integer', 'exists:exercises,id']], [
            'exercise_id.required' => 'Bitte wähle eine Übung aus.',
        ]);

        $item = $add->handle($workout, Exercise::query()->findOrFail($validated['exercise_id']));

        return to_route('workouts.edit', $workout)->withFragment('uebung-'.$item->id)->with('status', 'Übung hinzugefügt.');
    }

    public function destroy(Workout $workout): RedirectResponse
    {
        Gate::authorize('delete', $workout);

        $workout->delete();

        return to_route('training.index')->with('status', "„{$workout->name}“ wurde gelöscht.");
    }
}
