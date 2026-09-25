<?php

namespace App\Policies;

use App\Models\Contract;
use App\Models\User;

class ContractPolicy
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

    public function view(User $user, Contract $contract): bool
    {
        return $user->role !== null;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('label-manager')
            || $user->hasRole('ar');
    }

    public function update(User $user, Contract $contract): bool
    {
        return $user->hasRole('label-manager');
    }

    public function delete(User $user, Contract $contract): bool
    {
        return $user->hasRole('label-manager');
    }
}