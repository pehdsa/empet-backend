<?php

namespace App\Actions\PetReport;

use App\DTOs\PetReport\UpdatePetReportData;
use App\Enums\PetMatchStatus;
use App\Enums\PetReportStatus;
use App\Jobs\ProcessReportSightingMatching;
use App\Models\PetReport;
use Illuminate\Support\Facades\DB;

class UpdatePetReport
{
    /**
     * Update an existing lost pet report.
     */
    public function handle(UpdatePetReportData $data): PetReport
    {
        $report = $data->report;

        if ($report->status !== PetReportStatus::Lost) {
            abort(422, 'Only reports with LOST status can be updated.');
        }

        $locationChanged = false;

        DB::transaction(function () use ($data, $report, &$locationChanged): void {
            $updateData = [];

            if ($data->addressHint !== null) {
                $updateData['address_hint'] = $data->addressHint;
            }

            if ($data->description !== null) {
                $updateData['description'] = $data->description;
            }

            if ($data->lostAt !== null) {
                $updateData['lost_at'] = $data->lostAt;
            }

            if (! empty($updateData)) {
                $report->update($updateData);
            }

            if ($data->latitude !== null && $data->longitude !== null) {
                $current = PetReport::query()
                    ->withCoordinates()
                    ->find($report->id);

                if ((float) $current->latitude !== $data->latitude || (float) $current->longitude !== $data->longitude) {
                    DB::statement(
                        'UPDATE pet_reports SET location = ST_MakePoint(?, ?)::geography WHERE id = ?',
                        [$data->longitude, $data->latitude, $report->id]
                    );

                    $report->matches()
                        ->where('status', PetMatchStatus::Pending)
                        ->delete();

                    $locationChanged = true;
                }
            }
        });

        if ($locationChanged) {
            DB::afterCommit(fn () => ProcessReportSightingMatching::dispatch($report));
        }

        return $report->load(['pet', 'matches']);
    }
}
