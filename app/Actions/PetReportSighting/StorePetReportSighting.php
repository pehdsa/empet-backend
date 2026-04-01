<?php

namespace App\Actions\PetReportSighting;

use App\DTOs\PetReportSighting\StorePetReportSightingData;
use App\Models\PetReportSighting;
use App\Models\User;
use App\Notifications\PetReportSightingReported;
use Illuminate\Support\Facades\DB;

class StorePetReportSighting
{
    /**
     * Create a new pet report sighting.
     *
     * @return array{sighting: PetReportSighting, created: bool}
     */
    public function handle(StorePetReportSightingData $data, User $user): array
    {
        $existing = PetReportSighting::query()
            ->where('user_id', $user->id)
            ->where('report_id', $data->reportId)
            ->where('created_at', '>=', now()->subMinutes(5))
            ->first();

        if ($existing) {
            return ['sighting' => $existing, 'created' => false];
        }

        $sighting = DB::transaction(function () use ($data, $user): PetReportSighting {
            $sighting = PetReportSighting::create([
                'user_id' => $user->id,
                'report_id' => $data->reportId,
                'location' => DB::raw(sprintf(
                    'ST_MakePoint(%s, %s)::geography',
                    (float) $data->longitude,
                    (float) $data->latitude,
                )),
                'address_hint' => $data->addressHint,
                'description' => $data->description,
                'sighted_at' => $data->sightedAt,
                'share_phone' => $data->sharePhone,
                'is_active' => true,
            ]);

            return $sighting;
        });

        DB::afterCommit(function () use ($sighting) {
            $reportOwner = $sighting->report?->user;

            if ($reportOwner) {
                $reportOwner->notify(new PetReportSightingReported($sighting));
            }
        });

        return ['sighting' => $sighting, 'created' => true];
    }
}
