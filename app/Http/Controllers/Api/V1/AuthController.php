<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Auth\ChangePassword;
use App\Actions\Auth\LoginUser;
use App\Actions\Auth\LogoutUser;
use App\Actions\Auth\RegisterUser;
use App\Actions\Auth\ResetPassword;
use App\Actions\Auth\SendPasswordResetCode;
use App\Actions\Auth\VerifyPasswordResetCode;
use App\DTOs\Auth\ChangePasswordData;
use App\DTOs\Auth\ForgotPasswordData;
use App\DTOs\Auth\LoginUserData;
use App\DTOs\Auth\RegisterUserData;
use App\DTOs\Auth\ResetPasswordData;
use App\DTOs\Auth\VerifyResetCodeData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\VerifyResetCodeRequest;
use App\Http\Resources\MessageResource;
use App\Http\Resources\TokenResource;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

    public function user(Request $request): JsonResponse
    {
        return (new UserResource($request->user()))
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

    public function forgotPassword(ForgotPasswordRequest $request, SendPasswordResetCode $action): JsonResponse
    {
        $data = new ForgotPasswordData(
            email: $request->validated('email'),
        );

        $action->handle($data);

        return (new MessageResource('Se o e-mail estiver cadastrado, você receberá um código de recuperação.'))
            ->response()
            ->setStatusCode(200);
    }

    public function verifyResetCode(VerifyResetCodeRequest $request, VerifyPasswordResetCode $action): JsonResponse
    {
        $data = new VerifyResetCodeData(
            email: $request->validated('email'),
            code: $request->validated('code'),
        );

        $resetToken = $action->handle($data);

        return response()->json(['resetToken' => $resetToken]);
    }

    public function resetPassword(ResetPasswordRequest $request, ResetPassword $action): JsonResponse
    {
        $data = new ResetPasswordData(
            email: $request->validated('email'),
            resetToken: $request->validated('resetToken'),
            password: $request->validated('password'),
        );

        $action->handle($data);

        return (new MessageResource('Senha redefinida com sucesso.'))
            ->response()
            ->setStatusCode(200);
    }
}
