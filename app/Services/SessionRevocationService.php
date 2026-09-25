<?php

namespace App\Services;

use App\Models\LoginDevice;
use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

class SessionRevocationService
{
    /**
     * Revoke every token for the given user EXCEPT the one with $keepTokenId.
     * Also removes the corresponding login_device rows.
     *
     * Returns the number of tokens revoked.
     */
    public function revokeOthers(User $user, ?int $keepTokenId): int
    {
        $tokenQuery = PersonalAccessToken::where('tokenable_id', $user->id)
            ->where('tokenable_type', get_class($user));

        if ($keepTokenId) {
            $tokenQuery->where('id', '!=', $keepTokenId);
        }

        $count = (clone $tokenQuery)->count();
        $tokenQuery->delete();

        $deviceQuery = LoginDevice::where('user_id', $user->id);

        if ($keepTokenId) {
            $deviceQuery->where('token_id', '!=', $keepTokenId);
        }

        $deviceQuery->delete();

        return $count;
    }

    /**
     * Revoke every token for the given user, including the current one.
     * Also removes all login_device rows.
     *
     * Returns the number of tokens revoked.
     */
    public function revokeAll(User $user): int
    {
        $count = PersonalAccessToken::where('tokenable_id', $user->id)
            ->where('tokenable_type', get_class($user))
            ->count();

        PersonalAccessToken::where('tokenable_id', $user->id)
            ->where('tokenable_type', get_class($user))
            ->delete();

        LoginDevice::where('user_id', $user->id)->delete();

        return $count;
    }
}