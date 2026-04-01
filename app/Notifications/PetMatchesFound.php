<?php

namespace App\Notifications;

use App\Channels\PushChannel;
use App\DTOs\Notification\PushNotificationPayload;
use App\Models\PetReport;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PetMatchesFound extends Notification
{
    use Queueable;

    public function __construct(
        private readonly PetReport $report,
        private readonly int $matchesCount,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string|class-string>
     */
    public function via(object $notifiable): array
    {
        $setting = $notifiable->notificationSetting;

        if ($setting && ! $setting->notify_matches) {
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
            'matches_count' => $this->matchesCount,
        ];
    }

    /**
     * Get the push notification payload.
     */
    public function toPush(object $notifiable): PushNotificationPayload
    {
        $petName = $this->report->pet?->name ?? 'seu pet';

        return new PushNotificationPayload(
            title: 'Possíveis matches encontrados!',
            body: "Encontramos {$this->matchesCount} avistamento(s) que podem ser {$petName}",
            data: [
                'type' => 'matches_found',
                'report_id' => $this->report->id,
                'matches_count' => $this->matchesCount,
            ],
            category: 'matches',
        );
    }
}
