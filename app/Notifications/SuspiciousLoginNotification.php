<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SuspiciousLoginNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $ipAddress,
        public readonly string $browser,
        public readonly string $platform,
        public readonly string $device,
        public readonly string $loginTime,
        public readonly ?string $location = null,
        public readonly ?string $countryCode = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        $message = "New sign-in from a country you haven't used before: ";
        $message .= "{$this->browser} on {$this->platform} ({$this->device})";
        if ($this->location) {
            $message .= " — {$this->location}";
        }
        $message .= ". If this wasn't you, review your active sessions and change your password.";

        return [
            'title'         => 'Suspicious Sign-in Detected',
            'message'       => $message,
            'entity_type'   => 'SuspiciousLogin',
            'entity_id'     => $notifiable->id,
            'entity_code'   => 'SECURITY',
            'action_url'    => '/settings?tab=security',
            'context' => [
                'ip_address'   => $this->ipAddress,
                'browser'      => $this->browser,
                'platform'     => $this->platform,
                'device'       => $this->device,
                'login_time'   => $this->loginTime,
                'location'     => $this->location,
                'country_code' => $this->countryCode,
            ],
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Security alert — new sign-in to your Kay Factory Music account')
            ->greeting("Hello {$notifiable->name},")
            ->line("We noticed a sign-in to your account from a country you haven't used before.")
            ->line("Time: {$this->loginTime}")
            ->line("IP Address: {$this->ipAddress}")
            ->line("Browser: {$this->browser}")
            ->line("Platform: {$this->platform}")
            ->line("Device: {$this->device}");

        if ($this->location) {
            $mail->line("Approximate Location: {$this->location}");
        }

        return $mail
            ->line("If this was you, no action is needed — this country will be trusted on future logins.")
            ->line("If you don't recognize this activity, we strongly recommend:")
            ->line("• Reviewing your active sessions")
            ->line("• Changing your password immediately")
            ->action('Review Account Security', url('/staff/settings?tab=security'))
            ->salutation('— Kay Factory Music');
    }
}