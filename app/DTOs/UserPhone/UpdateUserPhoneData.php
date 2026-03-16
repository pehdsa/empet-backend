<?php

namespace App\DTOs\UserPhone;

use App\Models\UserPhone;

final readonly class UpdateUserPhoneData
{
    public function __construct(
        public UserPhone $userPhone,
        public string $phone,
        public bool $isWhatsapp,
        public bool $isPrimary,
        public ?string $label,
    ) {}
}
