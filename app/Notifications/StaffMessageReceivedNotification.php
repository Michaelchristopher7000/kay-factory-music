<?php

namespace App\Notifications;

use App\Models\StaffMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class StaffMessageReceivedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly StaffMessage $message,
        public readonly int $conversationId,
        public readonly string $senderName,
    ) {}

    /**
     * Database-only per spec — no email for internal staff messages.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'New Message',
            'message' => sprintf(
                'New message from %s: %s',
                $this->senderName,
                \Illuminate\Support\Str::limit($this->message->body, 100),
            ),
            'entity_type' => 'StaffMessage',
            'entity_id' => $this->message->id,
            'entity_code' => 'MSG',
            'action_url' => "/messages/{$this->conversationId}",
            'context' => [
                'conversation_id' => $this->conversationId,
                'sender_name' => $this->senderName,
            ],
        ];
    }
}