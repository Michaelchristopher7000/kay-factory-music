<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RoyaltyStatementIssuedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly int $statementId,
        public readonly string $statementCode,
        public readonly int $artistId,
        public readonly string $artistName,
        public readonly string $currency,
        public readonly string $totalRoyalty,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Royalty Statement Issued',
            'message' => "Royalty statement {$this->statementCode} for {$this->artistName} has been issued ({$this->currency} {$this->totalRoyalty}).",
            'entity_type' => 'RoyaltyStatement',
            'entity_id' => $this->statementId,
            'entity_code' => $this->statementCode,
            'action_url' => "/royalty-statements/{$this->statementId}",
            'context' => [
                'artist_id' => $this->artistId,
                'artist_name' => $this->artistName,
                'currency' => $this->currency,
                'total_royalty' => $this->totalRoyalty,
            ],
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Royalty Statement Issued: {$this->statementCode}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Royalty statement {$this->statementCode} has been issued.")
            ->line("Artist: {$this->artistName}")
            ->line("Total Royalty: {$this->currency} {$this->totalRoyalty}")
            ->action('View Statement', url("/royalty-statements/{$this->statementId}"))
            ->salutation('— Kay Factory Music');
    }
}