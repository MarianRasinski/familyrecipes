<?php

namespace App\Http\Controllers;

use App\Actions\SaveBodyProfile;
use App\Enums\ActivityLevel;
use App\Enums\FitnessGoal;
use App\Enums\Sex;
use App\Enums\TrainingType;
use App\Http\Requests\UpdateBodyProfileRequest;
use App\Models\BodyProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * „Profil & Ziele“: Körperdaten und Tagesbudget. Immer nur das eigene Profil – auch der Admin
 * sieht die Profile anderer nicht (deshalb gibt es keinen Benutzer-Parameter in der Route).
 */
class BodyProfileController extends Controller
{
    public function edit(Request $request): View
    {
        Gate::authorize('manage-body-profile');

        $profile = $request->user()->loadMissing('bodyProfile')->bodyProfile;

        return view('body-profile.edit', [
            'profile' => $profile ?? new BodyProfile([
                'training_days' => 0,
                'training_minutes' => 60,
                'training_type' => TrainingType::Strength,
            ]),
            'budget' => $profile?->budget(),
            'sexes' => Sex::cases(),
            'activityLevels' => ActivityLevel::cases(),
            'goals' => FitnessGoal::cases(),
            'trainingTypes' => TrainingType::cases(),
        ]);
    }

    public function update(UpdateBodyProfileRequest $request, SaveBodyProfile $save): RedirectResponse
    {
        $save->handle($request->user(), $request->validated());

        return to_route('body-profile.edit')->with('status', 'Profil gespeichert – dein Tagesbudget ist aktualisiert.');
    }
}
