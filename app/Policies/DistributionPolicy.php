<?php

namespace App\Policies;

use App\Models\Distribution;
use App\Models\User;

class DistributionPolicy
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

    public function view(User $user, Distribution $distribution): bool
    {
        return $user->role !== null;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('label-manager')
            || $user->hasRole('distribution-manager');
    }

    public function update(User $user, Distribution $distribution): bool
    {
        return $user->hasRole('label-manager')
            || $user->hasRole('distribution-manager');
    }

    public function delete(User $user, Distribution $distribution): bool
    {
        return $user->hasRole('label-manager');
    }
}