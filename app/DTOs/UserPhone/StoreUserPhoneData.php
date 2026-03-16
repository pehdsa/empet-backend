<?php

namespace App\DTOs\UserPhone;

final readonly class StoreUserPhoneData
{
    public function __construct(
        public string $phone,
        public bool $isWhatsapp,
        public bool $isPrimary,
        public ?string $label,
    ) {}
}
