<?php

namespace App\Actions\PetReport;

use App\Enums\PetMatchStatus;
use App\Enums\PetReportStatus;
use App\Models\PetReport;
use Illuminate\Support\Facades\DB;

class MarkPetReportFound
{
    /**
     * Mark a lost pet report as found.
     */
    public function handle(PetReport $report, ?int $confirmedMatchId = null): PetReport
    {
        if ($report->status !== PetReportStatus::Lost) {
            abort(422, 'Only reports with LOST status can be marked as found.');
        }

        if ($confirmedMatchId !== null) {
            $matchBelongsToReport = $report->matches()->where('id', $confirmedMatchId)->exists();
            if (! $matchBelongsToReport) {
                abort(422, 'The confirmed match does not belong to this report.');
            }
        }

        DB::transaction(function () use ($report, $confirmedMatchId): void {
            $report->update([
                'status' => PetReportStatus::Found,
                'found_at' => now(),
            ]);

            if ($confirmedMatchId !== null) {
                $report->matches()
                    ->where('id', $confirmedMatchId)
                    ->update(['status' => PetMatchStatus::Confirmed]);

                $report->matches()
                    ->where('id', '!=', $confirmedMatchId)
                    ->where('status', PetMatchStatus::Pending)
                    ->update(['status' => PetMatchStatus::Dismissed]);
            } else {
                $report->matches()
                    ->where('status', PetMatchStatus::Pending)
                    ->update(['status' => PetMatchStatus::Dismissed]);
            }
        });

        return $report;
    }
}
