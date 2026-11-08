<?php

namespace App\Http\Controllers;

use App\Enums\ExerciseLevel;
use App\Models\Equipment;
use App\Models\Exercise;
use App\Models\MuscleGroup;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Übungskatalog: für alle angemeldeten Familienmitglieder lesbar.
 */
class ExerciseController extends Controller
{
    public function index(Request $request): View
    {
        $level = ExerciseLevel::tryFrom($request->string('stufe')->value());

        $exercises = Exercise::query()
            ->with(['equipment', 'muscleGroups'])
            ->search($request->string('q')->trim()->value())
            ->forMuscleGroup($request->string('gruppe')->value())
            ->withEquipment($request->string('geraet')->value())
            ->ofLevel($level)
            ->when($request->boolean('zuhause'), fn ($query) => $query->doableAtHome())
            ->orderBy('name')
            ->get();

        return view('exercises.index', [
            'exercises' => $exercises,
            'muscleGroups' => MuscleGroup::query()->whereHas('exercises')->orderBy('name')->get(),
            'equipment' => Equipment::query()->whereHas('exercises')->orderBy('name')->get(),
            'levels' => ExerciseLevel::cases(),
            'activeLevel' => $level,
        ]);
    }

    public function show(Exercise $exercise): View
    {
        $exercise->load(['equipment', 'muscleGroups']);

        return view('exercises.show', [
            'exercise' => $exercise,
        ]);
    }
}
