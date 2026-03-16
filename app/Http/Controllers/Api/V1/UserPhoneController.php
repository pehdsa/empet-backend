<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\UserPhone\DeleteUserPhone;
use App\Actions\UserPhone\StoreUserPhone;
use App\Actions\UserPhone\UpdateUserPhone;
use App\DTOs\UserPhone\StoreUserPhoneData;
use App\DTOs\UserPhone\UpdateUserPhoneData;
use App\Http\Controllers\Controller;
use App\Http\Requests\UserPhone\StoreUserPhoneRequest;
use App\Http\Requests\UserPhone\UpdateUserPhoneRequest;
use App\Http\Resources\MessageResource;
use App\Http\Resources\UserPhoneResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserPhoneController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $phones = $request->user()->phones()
            ->orderByDesc('is_primary')
            ->orderBy('id')
            ->get();

        return UserPhoneResource::collection($phones);
    }

    public function store(StoreUserPhoneRequest $request, StoreUserPhone $action): JsonResponse
    {
        $data = new StoreUserPhoneData(
            phone: $request->validated('phone'),
            isWhatsapp: (bool) $request->validated('is_whatsapp', false),
            isPrimary: (bool) $request->validated('is_primary', false),
            label: $request->validated('label'),
        );

        $phone = $action->handle($data, $request->user());

        return (new UserPhoneResource($phone))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateUserPhoneRequest $request, int $id, UpdateUserPhone $action): UserPhoneResource
    {
        $phone = $request->user()->phones()->findOrFail($id);

        $data = new UpdateUserPhoneData(
            userPhone: $phone,
            phone: $request->validated('phone'),
            isWhatsapp: (bool) $request->validated('is_whatsapp', false),
            isPrimary: (bool) $request->validated('is_primary', false),
            label: $request->validated('label'),
        );

        $phone = $action->handle($data);

        return new UserPhoneResource($phone);
    }

    public function destroy(Request $request, int $id, DeleteUserPhone $action): JsonResponse
    {
        $phone = $request->user()->phones()->findOrFail($id);

        $action->handle($phone);

        return (new MessageResource('Phone deleted successfully.'))
            ->response()
            ->setStatusCode(200);
    }
}
