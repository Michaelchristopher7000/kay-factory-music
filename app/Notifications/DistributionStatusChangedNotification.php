<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DistributionStatusChangedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly int $distributionId,
        public readonly string $distributionCode,
        public readonly string $platform,
        public readonly string $oldStatus,
        public readonly string $newStatus,
        public readonly string $releaseTitle,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Distribution Status Changed',
            'message' => "Distribution {$this->distributionCode} on {$this->platform} changed from {$this->oldStatus} to {$this->newStatus}.",
            'entity_type' => 'Distribution',
            'entity_id' => $this->distributionId,
            'entity_code' => $this->distributionCode,
            'action_url' => "/distributions/{$this->distributionId}",
            'context' => [
                'platform' => $this->platform,
                'release_title' => $this->releaseTitle,
                'old_status' => $this->oldStatus,
                'new_status' => $this->newStatus,
            ],
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Distribution Status: {$this->distributionCode}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Distribution {$this->distributionCode} on {$this->platform} status changed.")
            ->line("Release: {$this->releaseTitle}")
            ->line("From: {$this->oldStatus}")
            ->line("To: {$this->newStatus}")
            ->action('View Distribution', url("/distributions/{$this->distributionId}"))
            ->salutation('— Kay Factory Music');
    }
}