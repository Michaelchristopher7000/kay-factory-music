<?php

namespace App\Observers;

use App\Models\Distribution;
use App\Notifications\DistributionStatusChangedNotification;
use App\Services\NotificationRecipients;
use Illuminate\Support\Facades\Notification;

class DistributionObserver
{
    public function updated(Distribution $distribution): void
    {
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return;
        }

        if (! $distribution->wasChanged('status')) {
            return;
        }

        $distribution->loadMissing('release');

        $recipients = NotificationRecipients::forRoles(
            ['super-admin', 'label-manager', 'distribution-manager'],
            excludeUserId: auth()->id(),
        );

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new DistributionStatusChangedNotification(
            distributionId: $distribution->id,
            distributionCode: $distribution->distribution_code,
            platform: $distribution->platform,
            oldStatus: (string) $distribution->getOriginal('status'),
            newStatus: (string) $distribution->status,
            releaseTitle: $distribution->release?->title ?? 'Unknown Release',
        ));
    }
}