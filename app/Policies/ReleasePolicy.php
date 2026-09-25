<?php

namespace App\Policies;

use App\Models\Release;
use App\Models\User;

class ReleasePolicy
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

    public function view(User $user, Release $release): bool
    {
        return $user->role !== null;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('label-manager')
            || $user->hasRole('ar')
            || $user->hasRole('artist-manager')
            || $user->hasRole('distribution-manager');
    }

    public function update(User $user, Release $release): bool
    {
        return $user->hasRole('label-manager')
            || $user->hasRole('ar')
            || $user->hasRole('artist-manager')
            || $user->hasRole('distribution-manager');
    }

    public function delete(User $user, Release $release): bool
    {
        return $user->hasRole('label-manager');
    }
}