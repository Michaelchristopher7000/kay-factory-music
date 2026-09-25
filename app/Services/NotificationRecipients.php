<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;

class NotificationRecipients
{
    /**
     * Return a deduplicated collection of users by role slug.
     *
     * @param  array<int, string>  $roleSlugs
     */
    public static function forRoles(
        array $roleSlugs,
        ?int $excludeUserId = null,
        ?User $additionalUser = null
    ): Collection {
        $users = User::query()
            ->whereHas('role', fn ($q) => $q->whereIn('slug', $roleSlugs))
            ->get();

        if ($additionalUser) {
            $users = $users->push($additionalUser);
        }

        $users = $users->unique('id');

        if ($excludeUserId !== null) {
            $users = $users->reject(fn (User $u) => $u->id === $excludeUserId);
        }

        return $users->values();
    }
}