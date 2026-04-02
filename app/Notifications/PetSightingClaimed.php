<?php

namespace App\Notifications;

use App\Channels\PushChannel;
use App\DTOs\Notification\PushNotificationPayload;
use App\Models\PetSighting;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PetSightingClaimed extends Notification
{
    use Queueable;

    public function __construct(
        private readonly PetSighting $sighting,
        private readonly User $claimer,
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
        $primaryPhone = $this->claimer->phones()->where('is_primary', true)->first();

        return [
            'sighting_id' => $this->sighting->id,
            'sighting_title' => $this->sighting->title,
            'claimer_name' => $this->claimer->name,
            'claimer_phone' => $primaryPhone?->phone,
            'claimer_phone_is_whatsapp' => $primaryPhone?->is_whatsapp,
        ];
    }

    /**
     * Get the push notification payload.
     */
    public function toPush(object $notifiable): PushNotificationPayload
    {
        $claimerName = $this->claimer->name ?? 'Alguém';

        return new PushNotificationPayload(
            title: 'Alguém reconheceu o pet que você avistou!',
            body: "{$claimerName} acredita que o pet do seu avistamento é dele.",
            data: [
                'type' => 'sighting_claimed',
                'sighting_id' => $this->sighting->id,
            ],
            category: 'sighting',
        );
    }
}
