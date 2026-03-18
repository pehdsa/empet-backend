<?php

namespace App\Actions\PetSighting;

use App\DTOs\PetSighting\StorePetSightingData;
use App\Models\PetSighting;
use App\Models\User;
use App\Notifications\PetSightingReported;
use Illuminate\Support\Facades\DB;

class StorePetSighting
{
    /**
     * Create a new pet sighting report.
     *
     * @return array{sighting: PetSighting, created: bool}
     */
    public function handle(StorePetSightingData $data, User $user): array
    {
        $existing = PetSighting::query()
            ->where('user_id', $user->id)
            ->where('report_id', $data->reportId)
            ->where('created_at', '>=', now()->subMinutes(5))
            ->first();

        if ($existing) {
            return ['sighting' => $existing, 'created' => false];
        }

        $sighting = DB::transaction(function () use ($data, $user): PetSighting {
            $sighting = PetSighting::create([
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
                $reportOwner->notify(new PetSightingReported($sighting));
            }
        });

        return ['sighting' => $sighting, 'created' => true];
    }
}
