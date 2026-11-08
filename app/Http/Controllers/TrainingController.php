<?php

namespace App\Http\Controllers;

use App\Actions\Training\CreateWorkoutFromTemplate;
use App\Actions\Training\SaveTrainingSchedule;
use App\Enums\ExerciseLevel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * „Training“: eigener Trainingsplan – Einheiten und welche Einheit an welchem Wochentag ansteht.
 * Jede Person sieht nur ihren eigenen Plan (keine Benutzer-ID in den Routen).
 */
class TrainingController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $workouts = $user->workouts()
            ->with(['exercises.exercise', 'schedule'])
            ->orderBy('name')
            ->get();

        return view('training.index', [
            'workouts' => $workouts,
            'schedule' => $user->scheduledWorkouts()->pluck('workout_id', 'weekday'),
            'levels' => ExerciseLevel::cases(),
            'recentSessions' => $user->workoutSessions()->orderByDesc('date')->orderByDesc('id')->limit(5)->get(),
            'profile' => $user->can('manage-body-profile') ? $user->loadMissing('bodyProfile')->bodyProfile : null,
        ]);
    }

    public function updateSchedule(Request $request, SaveTrainingSchedule $save): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'days' => ['array'],
            'days.*' => ['nullable', 'integer', Rule::exists('workouts', 'id')->where('user_id', $user->id)],
        ]);

        $days = collect($validated['days'] ?? [])->only(range(1, 7))->all();
        $save->handle($user, $days);

        return to_route('training.index')->with('status', 'Wochenplan gespeichert – die Trainingstage gelten ab sofort in „Mein Plan“.');
    }

    public function storeTemplate(Request $request, CreateWorkoutFromTemplate $create): RedirectResponse
    {
        $validated = $request->validate(['level' => ['required', Rule::enum(ExerciseLevel::class)]]);

        $workout = $create->handle($request->user(), ExerciseLevel::from($validated['level']));

        if (! $workout) {
            return back()->with('status', 'Im Übungskatalog gibt es keine passenden Übungen.');
        }

        return to_route('workouts.edit', $workout)->with('status', 'Einheit aus der Vorlage erstellt.');
    }
}
