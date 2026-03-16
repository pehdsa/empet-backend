<?php

namespace App\Actions\PetReport;

use App\DTOs\PetReport\StorePetReportData;
use App\Enums\PetReportStatus;
use App\Jobs\ProcessPetMatching;
use App\Models\PetReport;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class StorePetReport
{
    /**
     * Create a new lost pet report.
     */
    public function handle(StorePetReportData $data, User $user): PetReport
    {
        $report = DB::transaction(function () use ($data, $user): PetReport {
            $report = PetReport::create([
                'pet_id' => $data->petId,
                'user_id' => $user->id,
                'status' => PetReportStatus::Lost,
                'address_hint' => $data->addressHint,
                'description' => $data->description,
                'lost_at' => $data->lostAt,
                'is_active' => true,
            ]);

            DB::statement(
                'UPDATE pet_reports SET location = ST_MakePoint(?, ?)::geography WHERE id = ?',
                [$data->longitude, $data->latitude, $report->id]
            );

            return $report;
        });

        DB::afterCommit(fn () => ProcessPetMatching::dispatch($report));

        return $report->load('pet');
    }
}
