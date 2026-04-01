<?php

namespace App\Notifications;

use App\Channels\PushChannel;
use App\DTOs\Notification\PushNotificationPayload;
use App\Models\PetReportSighting;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PetReportSightingReported extends Notification
{
    use Queueable;

    public function __construct(
        private readonly PetReportSighting $sighting,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string|class-string>
     */
    public function via(object $notifiable): array
    {
        $setting = $notifiable->notificationSetting;

        if ($setting && ! $setting->notify_sightings) {
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
            'sighting_id' => $this->sighting->id,
            'report_id' => $this->sighting->report_id,
            'pet_name' => $this->sighting->report?->pet?->name,
            'address_hint' => $this->sighting->address_hint,
            'sighted_at' => $this->sighting->sighted_at?->toIso8601String(),
        ];
    }

    /**
     * Get the push notification payload.
     */
    public function toPush(object $notifiable): PushNotificationPayload
    {
        $petName = $this->sighting->report?->pet?->name ?? 'seu pet';
        $addressHint = $this->sighting->address_hint ?? 'uma localização próxima';

        return new PushNotificationPayload(
            title: "Alguém avistou {$petName}!",
            body: "Um avistamento foi reportado próximo a {$addressHint}",
            data: [
                'type' => 'report_sighting',
                'report_id' => $this->sighting->report_id,
                'sighting_id' => $this->sighting->id,
            ],
            category: 'sighting',
        );
    }
}
