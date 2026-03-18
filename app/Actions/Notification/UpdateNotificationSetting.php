<?php

namespace App\Actions\Notification;

use App\DTOs\Notification\UpdateNotificationSettingData;
use App\Models\User;
use App\Models\UserNotificationSetting;
use Illuminate\Support\Facades\DB;

class UpdateNotificationSetting
{
    /**
     * Update or create notification settings for the user.
     */
    public function handle(UpdateNotificationSettingData $data, User $user): UserNotificationSetting
    {
        $attributes = array_filter([
            'notify_lost_nearby' => $data->notifyLostNearby,
            'notify_matches' => $data->notifyMatches,
            'notify_sightings' => $data->notifySightings,
            'nearby_radius_km' => $data->nearbyRadiusKm,
        ], fn ($value) => $value !== null);

        $setting = UserNotificationSetting::updateOrCreate(
            ['user_id' => $user->id],
            $attributes,
        );

        if ($data->clearLocation) {
            DB::statement(
                'UPDATE user_notification_settings SET location = NULL WHERE id = ?',
                [$setting->id]
            );
        } elseif ($data->latitude !== null && $data->longitude !== null) {
            DB::statement(
                'UPDATE user_notification_settings SET location = ST_MakePoint(?, ?)::geography WHERE id = ?',
                [$data->longitude, $data->latitude, $setting->id]
            );
        }

        return $setting->fresh();
    }
}
