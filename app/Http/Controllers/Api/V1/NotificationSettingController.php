<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Notification\UpdateNotificationSetting;
use App\DTOs\Notification\UpdateNotificationSettingData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Notification\UpdateNotificationSettingRequest;
use App\Http\Resources\UserNotificationSettingResource;
use App\Models\UserNotificationSetting;
use Illuminate\Http\Request;

class NotificationSettingController extends Controller
{
    public function show(Request $request): UserNotificationSettingResource
    {
        $setting = $request->user()->notificationSetting;

        if (! $setting) {
            $setting = new UserNotificationSetting([
                'notify_lost_nearby' => true,
                'notify_matches' => true,
                'notify_sightings' => true,
                'nearby_radius_km' => 5,
            ]);
        } else {
            $setting = UserNotificationSetting::query()
                ->withCoordinates()
                ->find($setting->id);
        }

        return new UserNotificationSettingResource($setting);
    }

    public function update(UpdateNotificationSettingRequest $request, UpdateNotificationSetting $action): UserNotificationSettingResource
    {
        $data = new UpdateNotificationSettingData(
            notifyLostNearby: $request->has('notify_lost_nearby') ? (bool) $request->validated('notify_lost_nearby') : null,
            notifyMatches: $request->has('notify_matches') ? (bool) $request->validated('notify_matches') : null,
            notifySightings: $request->has('notify_sightings') ? (bool) $request->validated('notify_sightings') : null,
            nearbyRadiusKm: $request->has('nearby_radius_km') ? (int) $request->validated('nearby_radius_km') : null,
            latitude: $request->has('latitude') ? (float) $request->validated('latitude') : null,
            longitude: $request->has('longitude') ? (float) $request->validated('longitude') : null,
            clearLocation: (bool) $request->validated('clear_location', false),
        );

        $setting = $action->handle($data, $request->user());

        $setting = UserNotificationSetting::query()
            ->withCoordinates()
            ->find($setting->id);

        return new UserNotificationSettingResource($setting);
    }
}
