<?php

namespace App\Actions;

use App\Models\User;
use Carbon\CarbonImmutable;

/**
 * Markiert einen Tag als Trainingstag oder nimmt die Markierung zurück.
 */
class ToggleTrainingDay
{
    public function handle(User $user, CarbonImmutable $date, bool $isTrainingDay): void
    {
        $existing = $user->trainingDays()->whereDate('date', $date)->first();

        if ($isTrainingDay && ! $existing) {
            $user->trainingDays()->create(['date' => $date->toDateString()]);
        }

        if (! $isTrainingDay && $existing) {
            $existing->delete();
        }
    }
}
