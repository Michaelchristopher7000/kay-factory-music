<?php

namespace App\Policies;

use App\Models\Artist;
use App\Models\User;

class ArtistPolicy
{
    /**
     * Super Admin bypasses all checks.
     */
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

    public function view(User $user, Artist $artist): bool
    {
        return $user->role !== null;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('label-manager')
            || $user->hasRole('ar')
            || $user->hasRole('artist-manager');
    }

    public function update(User $user, Artist $artist): bool
    {
        return $user->hasRole('label-manager')
            || $user->hasRole('artist-manager');
    }

    public function delete(User $user, Artist $artist): bool
    {
        return $user->hasRole('label-manager');
    }
}