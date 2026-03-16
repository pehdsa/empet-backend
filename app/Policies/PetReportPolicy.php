<?php

namespace App\Policies;

use App\Enums\PetReportStatus;
use App\Enums\UserRole;
use App\Models\PetReport;
use App\Models\User;

class PetReportPolicy
{
    /**
     * Determine whether the user can view any reports.
     */
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::Client || $user->role === UserRole::Admin;
    }

    /**
     * Determine whether the user can view the report.
     */
    public function view(User $user, PetReport $report): bool
    {
        return $user->id === $report->user_id || $user->role === UserRole::Admin;
    }

    /**
     * Determine whether the user can create reports.
     */
    public function create(User $user): bool
    {
        return $user->role === UserRole::Client;
    }

    /**
     * Determine whether the user can update the report.
     */
    public function update(User $user, PetReport $report): bool
    {
        return $user->id === $report->user_id && $report->status === PetReportStatus::Lost;
    }

    /**
     * Determine whether the user can cancel the report.
     */
    public function cancel(User $user, PetReport $report): bool
    {
        return $user->id === $report->user_id && $report->status === PetReportStatus::Lost;
    }

    /**
     * Determine whether the user can mark the report as found.
     */
    public function markFound(User $user, PetReport $report): bool
    {
        return $user->id === $report->user_id && $report->status === PetReportStatus::Lost;
    }

    /**
     * Determine whether the user can view matches for the report.
     */
    public function viewMatches(User $user, PetReport $report): bool
    {
        return $user->id === $report->user_id || $user->role === UserRole::Admin;
    }

    /**
     * Determine whether the user can dismiss a match.
     */
    public function dismissMatch(User $user, PetReport $report): bool
    {
        return $user->id === $report->user_id;
    }

    /**
     * Determine whether the user can confirm a match.
     */
    public function confirmMatch(User $user, PetReport $report): bool
    {
        return $user->id === $report->user_id;
    }
}
