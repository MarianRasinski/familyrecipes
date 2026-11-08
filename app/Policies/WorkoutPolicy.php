<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workout;

/**
 * Trainingsplan: Einheiten sieht und ändert nur die Person, der sie gehören – auch der Admin nicht.
 */
class WorkoutPolicy
{
    public function view(User $user, Workout $workout): bool
    {
        return $workout->user_id === $user->id;
    }

    public function update(User $user, Workout $workout): bool
    {
        return $workout->user_id === $user->id;
    }

    public function delete(User $user, Workout $workout): bool
    {
        return $workout->user_id === $user->id;
    }
}
