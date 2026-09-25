<?php

namespace App\Observers;

use App\Models\RoyaltyPayment;
use App\Notifications\RoyaltyPaymentRecordedNotification;
use App\Services\NotificationRecipients;
use Illuminate\Support\Facades\Notification;

class RoyaltyPaymentObserver
{
    public function created(RoyaltyPayment $payment): void
    {
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return;
        }

        $payment->loadMissing(['statement', 'artist']);

        $recipients = NotificationRecipients::forRoles(
            ['super-admin', 'label-manager', 'finance-staff'],
            excludeUserId: auth()->id(),
        );

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new RoyaltyPaymentRecordedNotification(
            paymentId: $payment->id,
            paymentCode: $payment->payment_code,
            statementCode: $payment->statement?->statement_code ?? 'N/A',
            artistName: $payment->artist?->name ?? 'Unknown Artist',
            currency: $payment->currency,
            amount: (string) $payment->amount,
            method: $payment->method,
        ));
    }
}