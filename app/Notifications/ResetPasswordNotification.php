<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly string $token) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $expireMinutes = config('auth.passwords.users.expire', 60);

        $url = url('/staff/reset-password/' . $this->token
            . '?email=' . urlencode($notifiable->email));

        return (new MailMessage)
            ->subject('Reset your Kay Factory Music password')
            ->view('emails.reset-password', [
                'url' => $url,
                'name' => $notifiable->name,
                'email' => $notifiable->email,
                'expireMinutes' => $expireMinutes,
            ]);
    }
}