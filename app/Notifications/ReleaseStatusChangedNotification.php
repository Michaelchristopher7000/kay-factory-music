<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReleaseStatusChangedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly int $releaseId,
        public readonly string $releaseCode,
        public readonly string $releaseTitle,
        public readonly string $oldStatus,
        public readonly string $newStatus,
        public readonly string $artistName,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Release Status Changed',
            'message' => "Release {$this->releaseCode} ({$this->releaseTitle}) changed from {$this->oldStatus} to {$this->newStatus}.",
            'entity_type' => 'Release',
            'entity_id' => $this->releaseId,
            'entity_code' => $this->releaseCode,
            'action_url' => "/releases/{$this->releaseId}",
            'context' => [
                'artist_name' => $this->artistName,
                'old_status' => $this->oldStatus,
                'new_status' => $this->newStatus,
            ],
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Release Status: {$this->releaseCode}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Release {$this->releaseCode} status changed.")
            ->line("From: {$this->oldStatus}")
            ->line("To: {$this->newStatus}")
            ->line("Artist: {$this->artistName}")
            ->action('View Release', url("/releases/{$this->releaseId}"))
            ->salutation('— Kay Factory Music');
    }
}