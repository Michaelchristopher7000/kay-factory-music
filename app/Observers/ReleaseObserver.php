<?php

namespace App\Observers;

use App\Models\Release;
use App\Notifications\ReleaseCreatedNotification;
use App\Notifications\ReleaseStatusChangedNotification;
use App\Services\NotificationRecipients;
use Illuminate\Support\Facades\Notification;

class ReleaseObserver
{
    public function created(Release $release): void
    {
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return;
        }

        $release->loadMissing('artist');

        if (! $release->artist) {
            return;
        }

        $recipients = NotificationRecipients::forRoles(
            ['super-admin', 'label-manager', 'artist-manager', 'ar', 'distribution-manager'],
            excludeUserId: auth()->id(),
        );

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new ReleaseCreatedNotification(
            releaseId: $release->id,
            releaseCode: $release->release_code,
            releaseTitle: $release->title,
            releaseType: $release->type,
            artistName: $release->artist->name,
        ));
    }

    public function updated(Release $release): void
    {
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return;
        }

        if (! $release->wasChanged('status')) {
            return;
        }

        $release->loadMissing('artist');

        $recipients = NotificationRecipients::forRoles(
            ['super-admin', 'label-manager', 'artist-manager', 'distribution-manager'],
            excludeUserId: auth()->id(),
        );

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new ReleaseStatusChangedNotification(
            releaseId: $release->id,
            releaseCode: $release->release_code,
            releaseTitle: $release->title,
            oldStatus: (string) $release->getOriginal('status'),
            newStatus: (string) $release->status,
            artistName: $release->artist?->name ?? 'Unknown Artist',
        ));
    }
}