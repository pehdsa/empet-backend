<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\PetSighting;
use App\Models\User;

class PetSightingPolicy
{
    /**
     * Determine whether the user can view any sightings (feed).
     */
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::Client || $user->role === UserRole::Admin;
    }

    /**
     * Determine whether the user can view a sighting.
     */
    public function view(User $user, PetSighting $sighting): bool
    {
        return $user->role === UserRole::Client || $user->role === UserRole::Admin;
    }

    /**
     * Determine whether the user can create sightings.
     */
    public function create(User $user): bool
    {
        return $user->role === UserRole::Client;
    }

    /**
     * Determine whether the user can delete the sighting.
     */
    public function delete(User $user, PetSighting $sighting): bool
    {
        return $user->id === $sighting->user_id || $user->role === UserRole::Admin;
    }
}
