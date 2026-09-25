<?php

namespace App\Policies;

use App\Models\RoyaltyStatement;
use App\Models\User;

class RoyaltyStatementPolicy
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

    public function view(User $user, RoyaltyStatement $statement): bool
    {
        return $user->role !== null;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('label-manager')
            || $user->hasRole('finance-staff');
    }

    public function update(User $user, RoyaltyStatement $statement): bool
    {
        return $user->hasRole('label-manager')
            || $user->hasRole('finance-staff');
    }

    public function delete(User $user, RoyaltyStatement $statement): bool
    {
        return $user->hasRole('label-manager');
    }

    public function regenerate(User $user, RoyaltyStatement $statement): bool
    {
        return $user->hasRole('label-manager')
            || $user->hasRole('finance-staff');
    }
}