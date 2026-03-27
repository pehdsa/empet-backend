<?php

namespace App\Policies;

use App\Enums\PetReportStatus;
use App\Enums\UserRole;
use App\Models\PetReport;
use App\Models\PetSighting;
use App\Models\User;

class PetSightingPolicy
{
    /**
     * Determine whether the user can create sightings.
     */
    public function create(User $user): bool
    {
        return $user->role === UserRole::Client;
    }

    /**
     * Determine whether the user can view any sightings for a report.
     */
    public function viewAny(User $user, PetReport $report): bool
    {
        if (in_array($report->status, [PetReportStatus::Lost, PetReportStatus::Found])) {
            return $user->role === UserRole::Client || $user->role === UserRole::Admin;
        }

        return $user->id === $report->user_id || $user->role === UserRole::Admin;
    }

    /**
     * Determine whether the user can view the sighting.
     */
    public function view(User $user, PetSighting $sighting): bool
    {
        return $user->id === $sighting->report?->user_id
            || $user->id === $sighting->user_id
            || $user->role === UserRole::Admin;
    }
}
