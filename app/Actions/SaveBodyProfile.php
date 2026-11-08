<?php

namespace App\Actions;

use App\Models\BodyProfile;
use App\Models\User;

/**
 * Legt das Profil einer Person an oder aktualisiert es (ein Profil pro Person).
 */
class SaveBodyProfile
{
    /**
     * @param  array<string, mixed>  $data  validierte Daten aus UpdateBodyProfileRequest
     */
    public function handle(User $user, array $data): BodyProfile
    {
        // Über die Beziehung, damit user_id gesetzt wird, ohne mass-assignable zu sein
        $profile = $user->bodyProfile()->firstOrNew();

        $profile->fill([
            ...$data,
            'training_minutes' => $data['training_minutes'] ?? $profile->training_minutes ?? 60,
            'kcal_override' => filled($data['kcal_override'] ?? null) ? (int) $data['kcal_override'] : null,
        ])->save();

        return $profile;
    }
}
