<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RoyaltyPaymentRecordedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly int $paymentId,
        public readonly string $paymentCode,
        public readonly string $statementCode,
        public readonly string $artistName,
        public readonly string $currency,
        public readonly string $amount,
        public readonly string $method,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Royalty Payment Recorded',
            'message' => "Payment {$this->paymentCode} of {$this->currency} {$this->amount} recorded for {$this->artistName} (statement {$this->statementCode}).",
            'entity_type' => 'RoyaltyPayment',
            'entity_id' => $this->paymentId,
            'entity_code' => $this->paymentCode,
            'action_url' => "/royalty-payments/{$this->paymentId}",
            'context' => [
                'statement_code' => $this->statementCode,
                'artist_name' => $this->artistName,
                'currency' => $this->currency,
                'amount' => $this->amount,
                'method' => $this->method,
            ],
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Royalty Payment: {$this->paymentCode}")
            ->greeting("Hello {$notifiable->name},")
            ->line("A royalty payment has been recorded.")
            ->line("Payment Code: {$this->paymentCode}")
            ->line("Statement: {$this->statementCode}")
            ->line("Artist: {$this->artistName}")
            ->line("Amount: {$this->currency} {$this->amount}")
            ->line("Method: {$this->method}")
            ->action('View Payment', url("/royalty-payments/{$this->paymentId}"))
            ->salutation('— Kay Factory Music');
    }
}