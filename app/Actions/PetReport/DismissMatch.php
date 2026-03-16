<?php

namespace App\Actions\PetReport;

use App\Enums\PetMatchStatus;
use App\Enums\PetReportStatus;
use App\Models\PetMatch;

class DismissMatch
{
    /**
     * Dismiss a pending match.
     */
    public function handle(PetMatch $match): PetMatch
    {
        if ($match->report->status !== PetReportStatus::Lost) {
            abort(422, 'Matches can only be dismissed on reports with LOST status.');
        }

        if ($match->status !== PetMatchStatus::Pending) {
            abort(422, 'Only pending matches can be dismissed.');
        }

        $match->update(['status' => PetMatchStatus::Dismissed]);

        return $match;
    }
}
