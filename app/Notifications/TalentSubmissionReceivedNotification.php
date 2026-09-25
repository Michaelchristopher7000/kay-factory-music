<?php

namespace App\Notifications;

use App\Models\TalentSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TalentSubmissionReceivedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly TalentSubmission $submission,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'New Talent Submission',
            'message' => sprintf(
                '%s (%s) submitted a talent demo — %s.',
                $this->submission->full_name,
                $this->submission->talent_category,
                $this->submission->reference_number,
            ),
            'entity_type' => 'TalentSubmission',
            'entity_id' => $this->submission->id,
            'entity_code' => $this->submission->reference_number,
            'action_url' => "/talent-submissions/{$this->submission->id}",
            'context' => [
                'full_name' => $this->submission->full_name,
                'email' => $this->submission->email,
                'talent_category' => $this->submission->talent_category,
                'reference_number' => $this->submission->reference_number,
            ],
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = url("/staff/talent-submissions/{$this->submission->id}");

        return (new MailMessage)
            ->subject("New Talent Submission — {$this->submission->reference_number}")
            ->greeting("Hello {$notifiable->name},")
            ->line('A new talent submission has been received.')
            ->line("Reference: {$this->submission->reference_number}")
            ->line("Name: {$this->submission->full_name}")
            ->line("Category: {$this->submission->talent_category}")
            ->line("Email: {$this->submission->email}")
            ->line("Submitted: {$this->submission->created_at->toDayDateTimeString()}")
            ->action('View Submission', $url)
            ->salutation('— Kay Factory Music');
    }
}