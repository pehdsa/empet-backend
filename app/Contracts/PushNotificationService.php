<?php

namespace App\Contracts;

use App\DTOs\Notification\PushNotificationPayload;

interface PushNotificationService
{
    /**
     * Envia push notification para um ou mais devices.
     *
     * @param  array<string>  $providerDeviceIds
     */
    public function sendToDevices(array $providerDeviceIds, PushNotificationPayload $payload): bool;

    /**
     * Registra um device no provider externo e retorna o ID atribuído pelo provider.
     * Retorna null se o registro falhar.
     */
    public function registerDevice(string $externalUserId, string $deviceToken, string $platform): ?string;

    /**
     * Remove um device do provider externo.
     */
    public function removeDevice(string $providerDeviceId): bool;
}
