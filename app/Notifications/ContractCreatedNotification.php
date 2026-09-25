<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContractCreatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly int $contractId,
        public readonly string $contractCode,
        public readonly string $contractTitle,
        public readonly int $artistId,
        public readonly string $artistName,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'New Contract Created',
            'message' => "Contract {$this->contractCode} ({$this->contractTitle}) has been created for {$this->artistName}.",
            'entity_type' => 'Contract',
            'entity_id' => $this->contractId,
            'entity_code' => $this->contractCode,
            'action_url' => "/contracts/{$this->contractId}",
            'context' => [
                'artist_id' => $this->artistId,
                'artist_name' => $this->artistName,
            ],
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("New Contract: {$this->contractCode}")
            ->greeting("Hello {$notifiable->name},")
            ->line('A new contract has been created.')
            ->line("Contract Code: {$this->contractCode}")
            ->line("Title: {$this->contractTitle}")
            ->line("Artist: {$this->artistName}")
            ->action('View Contract', url("/contracts/{$this->contractId}"))
            ->salutation('— Kay Factory Music');
    }
}