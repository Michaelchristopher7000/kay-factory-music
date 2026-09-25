<?php

namespace App\Observers;

use App\Models\RoyaltyStatement;
use App\Notifications\RoyaltyStatementIssuedNotification;
use App\Services\NotificationRecipients;
use Illuminate\Support\Facades\Notification;

class RoyaltyStatementObserver
{
    public function updated(RoyaltyStatement $statement): void
    {
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return;
        }

        if (! $statement->wasChanged('status')) {
            return;
        }

        if ($statement->status !== RoyaltyStatement::STATUS_ISSUED) {
            return;
        }

        $statement->loadMissing('artist');

        if (! $statement->artist) {
            return;
        }

        $recipients = NotificationRecipients::forRoles(
            ['super-admin', 'label-manager', 'finance-staff'],
            excludeUserId: auth()->id(),
        );

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new RoyaltyStatementIssuedNotification(
            statementId: $statement->id,
            statementCode: $statement->statement_code,
            artistId: $statement->artist->id,
            artistName: $statement->artist->name,
            currency: $statement->currency,
            totalRoyalty: (string) $statement->total_royalty,
        ));
    }
}