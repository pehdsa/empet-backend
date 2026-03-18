<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Notification\RegisterDevice;
use App\Actions\Notification\RemoveDevice;
use App\DTOs\Notification\RegisterDeviceData;
use App\Enums\DevicePlatform;
use App\Http\Controllers\Controller;
use App\Http\Requests\Notification\RegisterDeviceRequest;
use App\Http\Resources\MessageResource;
use App\Http\Resources\UserDeviceResource;
use App\Models\UserDevice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserDeviceController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $devices = $request->user()
            ->devices()
            ->where('is_active', true)
            ->orderByDesc('last_active_at')
            ->paginateFromRequest();

        return UserDeviceResource::collection($devices);
    }

    public function store(RegisterDeviceRequest $request, RegisterDevice $action): JsonResponse
    {
        $data = new RegisterDeviceData(
            deviceToken: $request->validated('device_token'),
            platform: DevicePlatform::from($request->validated('platform')),
            deviceName: $request->validated('device_name'),
        );

        $device = $action->handle($data, $request->user());

        return (new UserDeviceResource($device))
            ->response()
            ->setStatusCode(201);
    }

    public function destroy(Request $request, UserDevice $userDevice, RemoveDevice $action): MessageResource
    {
        if ($userDevice->user_id !== $request->user()->id) {
            abort(404);
        }

        $action->handle($userDevice);

        return new MessageResource('Device removed successfully.');
    }
}
