<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ArtistCreatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly int $artistId,
        public readonly string $artistCode,
        public readonly string $artistName,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'New Artist Created',
            'message' => "Artist {$this->artistCode} ({$this->artistName}) has been created.",
            'entity_type' => 'Artist',
            'entity_id' => $this->artistId,
            'entity_code' => $this->artistCode,
            'action_url' => "/artists/{$this->artistId}",
            'context' => [
                'artist_name' => $this->artistName,
            ],
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("New Artist: {$this->artistCode}")
            ->greeting("Hello {$notifiable->name},")
            ->line('A new artist has been created.')
            ->line("Artist Code: {$this->artistCode}")
            ->line("Name: {$this->artistName}")
            ->action('View Artist', url("/artists/{$this->artistId}"))
            ->salutation('— Kay Factory Music');
    }
}