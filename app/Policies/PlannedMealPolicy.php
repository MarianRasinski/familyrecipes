<?php

namespace App\Policies;

use App\Models\PlannedMeal;
use App\Models\User;

/**
 * Persönlicher Plan: ändern und löschen darf nur die Person, der der Eintrag gehört – auch der Admin nicht.
 */
class PlannedMealPolicy
{
    public function update(User $user, PlannedMeal $entry): bool
    {
        return $entry->user_id === $user->id;
    }

    public function delete(User $user, PlannedMeal $entry): bool
    {
        return $entry->user_id === $user->id;
    }
}
