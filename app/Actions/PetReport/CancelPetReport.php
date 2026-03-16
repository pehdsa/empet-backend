<?php

namespace App\Actions\PetReport;

use App\Enums\PetMatchStatus;
use App\Enums\PetReportStatus;
use App\Models\PetReport;
use Illuminate\Support\Facades\DB;

class CancelPetReport
{
    /**
     * Cancel a lost pet report.
     */
    public function handle(PetReport $report): PetReport
    {
        if ($report->status !== PetReportStatus::Lost) {
            abort(422, 'Only reports with LOST status can be cancelled.');
        }

        DB::transaction(function () use ($report): void {
            $report->update(['status' => PetReportStatus::Cancelled]);

            $report->matches()
                ->where('status', PetMatchStatus::Pending)
                ->update(['status' => PetMatchStatus::Dismissed]);
        });

        return $report;
    }
}
