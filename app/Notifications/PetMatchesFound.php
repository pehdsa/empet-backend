<?php

namespace App\Notifications;

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
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
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
}
