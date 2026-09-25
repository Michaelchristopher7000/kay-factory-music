<?php

namespace App\Observers;

use App\Models\Contract;
use App\Notifications\ContractCreatedNotification;
use App\Notifications\ContractUpdatedNotification;
use App\Services\NotificationRecipients;
use Illuminate\Support\Facades\Notification;

class ContractObserver
{
    public function created(Contract $contract): void
    {
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return;
        }

        $contract->loadMissing('artist');

        if (! $contract->artist) {
            return;
        }

        $recipients = NotificationRecipients::forRoles(
            ['super-admin', 'label-manager', 'artist-manager', 'ar'],
            excludeUserId: auth()->id(),
        );

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new ContractCreatedNotification(
            contractId: $contract->id,
            contractCode: $contract->contract_code,
            contractTitle: $contract->title,
            artistId: $contract->artist->id,
            artistName: $contract->artist->name,
        ));
    }

    public function updated(Contract $contract): void
    {
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return;
        }

        $changed = array_keys($contract->getChanges());
        $watch = ['status', 'royalty_rate', 'advance_amount', 'end_date'];

        $intersect = array_values(array_intersect($changed, $watch));

        if (empty($intersect)) {
            return;
        }

        $contract->loadMissing('artist');

        if (! $contract->artist) {
            return;
        }

        $recipients = NotificationRecipients::forRoles(
            ['super-admin', 'label-manager', 'artist-manager'],
            excludeUserId: auth()->id(),
        );

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new ContractUpdatedNotification(
            contractId: $contract->id,
            contractCode: $contract->contract_code,
            contractTitle: $contract->title,
            artistName: $contract->artist->name,
            changedFields: $intersect,
        ));
    }
}