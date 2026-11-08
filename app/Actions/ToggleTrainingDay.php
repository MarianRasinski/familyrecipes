<?php

namespace App\Actions;

use App\Models\User;
use Carbon\CarbonImmutable;

/**
 * Legt fest, ob an einem Tag trainiert wird. Entspricht das dem Wochenplan, wird keine Abweichung gespeichert.
 */
class ToggleTrainingDay
{
    public function handle(User $user, CarbonImmutable $date, bool $isTrainingDay): void
    {
        $scheduled = $user->scheduledWorkouts()->where('weekday', $date->dayOfWeekIso)->exists();
        $existing = $user->trainingDays()->whereDate('date', $date)->first();

        if ($isTrainingDay === $scheduled) {
            $existing?->delete();

            return;
        }

        if ($existing) {
            $existing->update(['is_training' => $isTrainingDay]);
        } else {
            $user->trainingDays()->create(['date' => $date->toDateString(), 'is_training' => $isTrainingDay]);
        }
    }
}
