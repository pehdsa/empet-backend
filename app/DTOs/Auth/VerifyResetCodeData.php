<?php

namespace App\DTOs\Auth;

final readonly class VerifyResetCodeData
{
    public function __construct(
        public string $email,
        public string $code,
    ) {}
}
