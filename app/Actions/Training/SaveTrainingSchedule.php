<?php

namespace App\Actions\Training;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Speichert den Wochenplan: je Wochentag (1–7) eine eigene Einheit oder Ruhetag (null).
 */
class SaveTrainingSchedule
{
    /**
     * @param  array<int|string, int|string|null>  $days  Wochentag → workout_id
     */
    public function handle(User $user, array $days): void
    {
        DB::transaction(function () use ($user, $days) {
            $user->scheduledWorkouts()->delete();

            foreach ($days as $weekday => $workoutId) {
                if (filled($workoutId)) {
                    $user->scheduledWorkouts()->create(['weekday' => (int) $weekday, 'workout_id' => (int) $workoutId]);
                }
            }
        });
    }
}
