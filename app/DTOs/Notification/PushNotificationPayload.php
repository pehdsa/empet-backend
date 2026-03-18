<?php

namespace App\DTOs\Notification;

final readonly class PushNotificationPayload
{
    /**
     * @param  array<string, mixed>  $data  Dados extras para deep linking
     */
    public function __construct(
        public string $title,
        public string $body,
        public ?string $imageUrl = null,
        public array $data = [],
        public ?string $category = null,
    ) {}
}
