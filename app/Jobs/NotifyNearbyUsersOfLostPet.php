<?php

namespace App\Jobs;

use App\Models\PetReport;
use App\Models\User;
use App\Models\UserNotificationSetting;
use App\Notifications\PetLostNearby;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NotifyNearbyUsersOfLostPet implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var int */
    public $tries = 3;

    /** @var array<int, int> */
    public $backoff = [10, 60];

    public function __construct(
        private readonly PetReport $report,
    ) {}

    public function uniqueId(): string
    {
        return 'notify-nearby:'.$this->report->id;
    }

    public function handle(): void
    {
        $report = $this->report->loadMissing('pet');

        $coordinates = DB::selectOne(
            'SELECT ST_Y(location::geometry) as latitude, ST_X(location::geometry) as longitude FROM pet_reports WHERE id = ?',
            [$report->id],
        );

        if (! $coordinates) {
            Log::warning('NotifyNearbyUsersOfLostPet: report has no location', [
                'report_id' => $report->id,
            ]);

            return;
        }

        $longitude = $coordinates->longitude;
        $latitude = $coordinates->latitude;

        UserNotificationSetting::query()
            ->select('user_notification_settings.id', 'user_notification_settings.user_id')
            ->selectRaw('ST_Distance(location, ST_MakePoint(?, ?)::geography) as distance_meters', [$longitude, $latitude])
            ->where('notify_lost_nearby', true)
            ->whereNotNull('location')
            ->where('user_id', '!=', $report->user_id)
            ->whereRaw('ST_DWithin(location, ST_MakePoint(?, ?)::geography, nearby_radius_km * 1000)', [$longitude, $latitude])
            ->chunkById(100, function ($settings) use ($report) {
                foreach ($settings as $setting) {
                    $this->notifyUser($setting->user_id, $report, $setting->distance_meters);
                }
            }, 'id');
    }

    private function notifyUser(int $userId, PetReport $report, float $distanceMeters): void
    {
        $alreadyNotified = DB::table('notifications')
            ->where('type', PetLostNearby::class)
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $userId)
            ->whereRaw("data::jsonb->>'report_id' = ?", [(string) $report->id])
            ->exists();

        if ($alreadyNotified) {
            return;
        }

        $user = User::find($userId);

        if (! $user) {
            return;
        }

        $distanceKm = $distanceMeters / 1000;
        $user->notify(new PetLostNearby($report, $distanceKm));
    }
}
