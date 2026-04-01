<?php

namespace App\Actions\PetReport;

use App\Enums\PetMatchStatus;
use App\Enums\PetReportStatus;
use App\Models\PetMatch;

class ConfirmMatch
{
    /**
     * Confirm a pending match, marking the report as found.
     */
    public function handle(PetMatch $match, MarkPetReportFound $markFound): PetMatch
    {
        if ($match->report->status !== PetReportStatus::Lost) {
            abort(422, 'Matches can only be confirmed on reports with LOST status.');
        }

        if ($match->status !== PetMatchStatus::Pending) {
            abort(422, 'Only pending matches can be confirmed.');
        }

        $markFound->handle($match->report, $match->id);

        $match->report->matches()
            ->where('id', '!=', $match->id)
            ->where('status', PetMatchStatus::Dismissed)
            ->delete();

        return $match->refresh()->load('report');
    }
}
