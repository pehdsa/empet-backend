<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Auth\ChangePassword;
use App\Actions\Auth\LoginUser;
use App\Actions\Auth\LogoutUser;
use App\Actions\Auth\RegisterUser;
use App\DTOs\Auth\ChangePasswordData;
use App\DTOs\Auth\LoginUserData;
use App\DTOs\Auth\RegisterUserData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\MessageResource;
use App\Http\Resources\TokenResource;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function register(RegisterRequest $request, RegisterUser $action): JsonResponse
    {
        $data = new RegisterUserData(
            name: $request->validated('name'),
            email: $request->validated('email'),
            password: $request->validated('password'),
        );

        $result = $action->handle($data);

        return (new TokenResource($result))
            ->response()
            ->setStatusCode(201);
    }

    public function login(LoginRequest $request, LoginUser $action): JsonResponse
    {
        $data = new LoginUserData(
            email: $request->validated('email'),
            password: $request->validated('password'),
        );

        $result = $action->handle($data);

        return (new TokenResource($result))
            ->response()
            ->setStatusCode(200);
    }

    public function changePassword(ChangePasswordRequest $request, ChangePassword $action): JsonResponse
    {
        $data = new ChangePasswordData(
            user: $request->user(),
            password: $request->validated('password'),
        );

        $action->handle($data);

        return (new MessageResource('Password changed successfully.'))
            ->response()
            ->setStatusCode(200);
    }

    public function logout(LogoutUser $action): JsonResponse
    {
        $action->handle(request()->user());

        return (new MessageResource('Logged out successfully.'))
            ->response()
            ->setStatusCode(200);
    }
}
