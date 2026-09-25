<?php

namespace App\Policies;

use App\Models\RoyaltyStatement;
use App\Models\RoyaltyStatementLine;
use App\Models\User;

class RoyaltyStatementLinePolicy
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

    public function view(User $user, RoyaltyStatementLine $line): bool
    {
        return $user->role !== null;
    }

    public function update(User $user, RoyaltyStatementLine $line): bool
    {
        if ($line->statement?->status !== RoyaltyStatement::STATUS_DRAFT) {
            return false;
        }

        return $user->hasRole('label-manager')
            || $user->hasRole('finance-staff');
    }

    public function delete(User $user, RoyaltyStatementLine $line): bool
    {
        if ($line->statement?->status !== RoyaltyStatement::STATUS_DRAFT) {
            return false;
        }

        return $user->hasRole('label-manager')
            || $user->hasRole('finance-staff');
    }
}