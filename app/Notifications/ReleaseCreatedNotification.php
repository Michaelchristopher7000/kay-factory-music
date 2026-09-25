<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReleaseCreatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly int $releaseId,
        public readonly string $releaseCode,
        public readonly string $releaseTitle,
        public readonly string $releaseType,
        public readonly string $artistName,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'New Release Created',
            'message' => "Release {$this->releaseCode} ({$this->releaseTitle}) by {$this->artistName} was created.",
            'entity_type' => 'Release',
            'entity_id' => $this->releaseId,
            'entity_code' => $this->releaseCode,
            'action_url' => "/releases/{$this->releaseId}",
            'context' => [
                'artist_name' => $this->artistName,
                'release_type' => $this->releaseType,
            ],
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("New Release: {$this->releaseCode}")
            ->greeting("Hello {$notifiable->name},")
            ->line('A new release has been created.')
            ->line("Release Code: {$this->releaseCode}")
            ->line("Title: {$this->releaseTitle}")
            ->line("Type: {$this->releaseType}")
            ->line("Artist: {$this->artistName}")
            ->action('View Release', url("/releases/{$this->releaseId}"))
            ->salutation('— Kay Factory Music');
    }
}