<?php

namespace App\Notifications;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContactMessageReceivedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly ContactMessage $message,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        $categoryLabel = ContactMessage::CATEGORIES[$this->message->category] ?? $this->message->category;

        return [
            'title' => 'New Contact Message',
            'message' => sprintf(
                '%s sent a %s message — %s.',
                $this->message->name,
                $categoryLabel,
                $this->message->reference_number,
            ),
            'entity_type' => 'ContactMessage',
            'entity_id' => $this->message->id,
            'entity_code' => $this->message->reference_number,
            'action_url' => "/contact-messages/{$this->message->id}",
            'context' => [
                'name' => $this->message->name,
                'email' => $this->message->email,
                'category' => $this->message->category,
                'subject' => $this->message->subject,
                'reference_number' => $this->message->reference_number,
            ],
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = url("/staff/contact-messages/{$this->message->id}");
        $categoryLabel = ContactMessage::CATEGORIES[$this->message->category] ?? $this->message->category;

        return (new MailMessage)
            ->subject("New Contact Message — {$this->message->reference_number}")
            ->greeting("Hello {$notifiable->name},")
            ->line('A new contact message has been received.')
            ->line("Reference: {$this->message->reference_number}")
            ->line("From: {$this->message->name} ({$this->message->email})")
            ->line("Category: {$categoryLabel}")
            ->line("Subject: {$this->message->subject}")
            ->line('Preview: ' . \Illuminate\Support\Str::limit($this->message->message, 160))
            ->action('View Message', $url)
            ->salutation('— Kay Factory Music');
    }
}