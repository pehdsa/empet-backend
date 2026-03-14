<?php

namespace App\Actions\Auth;

use App\DTOs\Auth\RegisterUserData;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RegisterUser
{
    /**
     * Register a new user or restore a soft-deleted one.
     *
     * @return array{user: User, token: string}
     */
    public function handle(RegisterUserData $data): array
    {
        return DB::transaction(function () use ($data): array {
            $existingUser = User::withTrashed()
                ->where('email', $data->email)
                ->first();

            if ($existingUser && $existingUser->trashed()) {
                $existingUser->restore();
                $existingUser->update([
                    'name' => $data->name,
                    'password' => $data->password,
                    'role' => UserRole::Client,
                    'is_active' => true,
                    'email_verified_at' => null,
                ]);

                $user = $existingUser;
            } else {
                $user = User::create([
                    'name' => $data->name,
                    'email' => $data->email,
                    'password' => $data->password,
                    'role' => UserRole::Client,
                    'is_active' => true,
                ]);
            }

            $token = $user->createToken('api')->plainTextToken;

            return ['user' => $user, 'token' => $token];
        });
    }
}
