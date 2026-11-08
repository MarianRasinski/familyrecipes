<?php

namespace App\Http\Controllers;

use App\Actions\Training\SaveWorkoutSession;
use App\Actions\Training\StartWorkoutSession;
use App\Http\Requests\UpdateWorkoutSessionRequest;
use App\Models\Workout;
use App\Models\WorkoutSession;
use App\Support\Training\ExerciseHistory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Trainingsprotokoll: Training starten, Sätze eintragen/abhaken, abschließen, Verlauf ansehen.
 */
class WorkoutSessionController extends Controller
{
    public function index(Request $request): View
    {
        return view('training.sessions', [
            'sessions' => $request->user()->workoutSessions()
                ->with('sets')
                ->orderByDesc('date')
                ->orderByDesc('id')
                ->paginate(20),
        ]);
    }

    public function store(Workout $workout, StartWorkoutSession $start): RedirectResponse
    {
        Gate::authorize('update', $workout);

        $session = $start->handle($workout);

        return to_route('workout-sessions.show', $session);
    }

    public function show(Request $request, WorkoutSession $workoutSession): View
    {
        Gate::authorize('view', $workoutSession);

        $workoutSession->load('sets.exercise');

        return view('training.session', [
            'session' => $workoutSession,
            'groups' => $workoutSession->setsByExercise(),
            'last' => ExerciseHistory::lastSets(
                $request->user(),
                $workoutSession->sets->pluck('exercise_id'),
                $workoutSession->id,
            ),
        ]);
    }

    public function update(UpdateWorkoutSessionRequest $request, WorkoutSession $workoutSession, SaveWorkoutSession $save): RedirectResponse
    {
        $finish = $request->boolean('finish');
        $addSetAt = $request->filled('add_set') ? (int) $request->validated('add_set') : null;
        $removeSet = $request->filled('remove_set') ? (int) $request->validated('remove_set') : null;
        // Nach dem Hinzufügen/Entfernen zurück zur selben Übung springen
        $position = $addSetAt ?? ($removeSet ? $workoutSession->sets()->whereKey($removeSet)->value('position') : null);

        $save->handle($workoutSession, $request->validated('sets') ?? [], $request->validated('notes'), $finish, $addSetAt, $removeSet);

        if ($finish) {
            return to_route('workout-sessions.index')->with('status', 'Training abgeschlossen – stark! 💪');
        }

        $redirect = to_route('workout-sessions.show', $workoutSession);

        return $position
            ? $redirect->withFragment('uebung-'.$position)
            : $redirect->with('status', 'Zwischengespeichert.');
    }

    public function destroy(WorkoutSession $workoutSession): RedirectResponse
    {
        Gate::authorize('delete', $workoutSession);

        $workoutSession->delete();

        return to_route('workout-sessions.index')->with('status', 'Training gelöscht.');
    }
}
