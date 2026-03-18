<?php

namespace App\Notifications;

use App\Channels\PushChannel;
use App\DTOs\Notification\PushNotificationPayload;
use App\Models\PetReport;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PetLostNearby extends Notification
{
    use Queueable;

    public function __construct(
        private readonly PetReport $report,
        private readonly float $distanceKm,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string|class-string>
     */
    public function via(object $notifiable): array
    {
        $setting = $notifiable->notificationSetting;

        if ($setting && ! $setting->notify_lost_nearby) {
            return [];
        }

        return ['database', PushChannel::class];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'report_id' => $this->report->id,
            'pet_name' => $this->report->pet?->name,
            'pet_species' => $this->report->pet?->species?->value,
            'address_hint' => $this->report->address_hint,
            'distance_km' => round($this->distanceKm, 1),
        ];
    }

    /**
     * Get the push notification payload.
     */
    public function toPush(object $notifiable): PushNotificationPayload
    {
        $petName = $this->report->pet?->name ?? 'Um pet';
        $species = $this->report->pet?->species?->value ?? 'pet';
        $addressHint = $this->report->address_hint ?? 'sua região';

        return new PushNotificationPayload(
            title: 'Pet perdido perto de você',
            body: "{$petName} ({$species}) foi visto pela última vez próximo a {$addressHint}",
            data: [
                'type' => 'lost_nearby',
                'report_id' => $this->report->id,
            ],
            category: 'lost_nearby',
        );
    }
}
