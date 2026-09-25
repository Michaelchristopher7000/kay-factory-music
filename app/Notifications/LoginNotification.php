<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoginNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $ipAddress,
        public readonly string $browser,
        public readonly string $platform,
        public readonly string $device,
        public readonly string $loginTime,
        public readonly ?string $location = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        $message = "New sign-in from {$this->browser} on {$this->platform} ({$this->device}) at {$this->loginTime}";
        $message .= $this->location ? " — {$this->location}" : " — IP {$this->ipAddress}";

        return [
            'title' => 'New Login Detected',
            'message' => $message . '.',
            'entity_type' => 'Login',
            'entity_id' => $notifiable->id,
            'entity_code' => 'LOGIN',
            'action_url' => '/security',
            'context' => [
                'ip_address' => $this->ipAddress,
                'browser' => $this->browser,
                'platform' => $this->platform,
                'device' => $this->device,
                'login_time' => $this->loginTime,
                'location' => $this->location,
            ],
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('New sign-in to your Kay Factory Music account')
            ->greeting("Hello {$notifiable->name},")
            ->line('We noticed a new sign-in to your account.')
            ->line("Time: {$this->loginTime}")
            ->line("IP Address: {$this->ipAddress}")
            ->line("Browser: {$this->browser}")
            ->line("Platform: {$this->platform}")
            ->line("Device: {$this->device}");

        if ($this->location) {
            $mail->line("Approximate Location: {$this->location}");
        }

        return $mail
            ->line("If this was you, no action is needed. If you don't recognize this activity, please reset your password immediately.")
            ->action('Review Account Security', url('/staff/security'))
            ->salutation('— Kay Factory Music');
    }
}