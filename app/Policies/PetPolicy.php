<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Pet;
use App\Models\User;

class PetPolicy
{
    /**
     * Determine whether the user can view any pets.
     */
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::Client || $user->role === UserRole::Admin;
    }

    /**
     * Determine whether the user can view the pet.
     */
    public function view(User $user, Pet $pet): bool
    {
        return $user->id === $pet->user_id || $user->role === UserRole::Admin;
    }

    /**
     * Determine whether the user can create pets.
     */
    public function create(User $user): bool
    {
        return $user->role === UserRole::Client;
    }

    /**
     * Determine whether the user can update the pet.
     */
    public function update(User $user, Pet $pet): bool
    {
        return $user->id === $pet->user_id;
    }

    /**
     * Determine whether the user can delete the pet.
     */
    public function delete(User $user, Pet $pet): bool
    {
        return $user->id === $pet->user_id;
    }

    /**
     * Determine whether the user can toggle the pet's active status.
     */
    public function toggleActive(User $user, Pet $pet): bool
    {
        return $user->role === UserRole::Admin;
    }
}
