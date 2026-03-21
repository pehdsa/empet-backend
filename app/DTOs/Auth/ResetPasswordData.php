<?php

namespace App\DTOs\Auth;

final readonly class ResetPasswordData
{
    public function __construct(
        public string $email,
        public string $resetToken,
        public string $password,
    ) {}
}
