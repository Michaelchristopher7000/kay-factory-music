<?php

namespace App\Policies;

use App\Models\Track;
use App\Models\User;

class TrackPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->role !== null;
    }

    public function view(User $user, Track $track): bool
    {
        return $user->role !== null;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('label-manager')
            || $user->hasRole('ar')
            || $user->hasRole('artist-manager');
    }

    public function update(User $user, Track $track): bool
    {
        return $user->hasRole('label-manager')
            || $user->hasRole('ar')
            || $user->hasRole('artist-manager');
    }

    public function delete(User $user, Track $track): bool
    {
        return $user->hasRole('label-manager');
    }
}