<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContractUpdatedNotification extends Notification
{
    use Queueable;

    /**
     * @param  array<int, string>  $changedFields
     */
    public function __construct(
        public readonly int $contractId,
        public readonly string $contractCode,
        public readonly string $contractTitle,
        public readonly string $artistName,
        public readonly array $changedFields,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        $fields = implode(', ', $this->changedFields);

        return [
            'title' => 'Contract Updated',
            'message' => "Contract {$this->contractCode} for {$this->artistName} was updated ({$fields}).",
            'entity_type' => 'Contract',
            'entity_id' => $this->contractId,
            'entity_code' => $this->contractCode,
            'action_url' => "/contracts/{$this->contractId}",
            'context' => [
                'artist_name' => $this->artistName,
                'changed_fields' => $this->changedFields,
            ],
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Contract Updated: {$this->contractCode}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Contract {$this->contractCode} has been updated.")
            ->line("Title: {$this->contractTitle}")
            ->line('Changed: ' . implode(', ', $this->changedFields))
            ->action('View Contract', url("/contracts/{$this->contractId}"))
            ->salutation('— Kay Factory Music');
    }
}