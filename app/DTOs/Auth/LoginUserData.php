<?php

namespace App\DTOs\Auth;

final readonly class LoginUserData
{
    public function __construct(
        public string $email,
        public string $password,
    ) {}
}
