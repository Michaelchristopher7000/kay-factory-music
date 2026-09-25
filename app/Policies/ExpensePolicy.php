<?php

namespace App\Policies;

use App\Models\Expense;
use App\Models\User;

class ExpensePolicy
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

    public function view(User $user, Expense $expense): bool
    {
        return $user->role !== null;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('label-manager')
            || $user->hasRole('finance-staff');
    }

    public function update(User $user, Expense $expense): bool
    {
        return $user->hasRole('label-manager')
            || $user->hasRole('finance-staff');
    }

    public function delete(User $user, Expense $expense): bool
    {
        return $user->hasRole('label-manager');
    }
}