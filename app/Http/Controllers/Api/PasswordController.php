<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\ChangePasswordRequest;
use App\Models\AuditLog;
use App\Services\SessionRevocationService;
use Illuminate\Http\JsonResponse;

class PasswordController extends Controller
{
    public function __construct(
        private readonly SessionRevocationService $sessions,
    ) {}

    /**
     * Change the authenticated user's password.
     * Requires the current password for verification.
     * Revokes all OTHER sessions after a successful change.
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();
        $currentTokenId = $user->currentAccessToken()?->id;

        // Password is auto-hashed by the User model's `password => hashed` cast.
        $user->password = $request->input('password');
        $user->save();

        // Log password change
        AuditLog::log('password_changed', $user, [
            'attributes' => [
                'changed' => true,
                'at' => now()->toIso8601String(),
            ],
        ], $user);

        // Revoke every OTHER session (keep the current one alive)
        $revokedCount = $this->sessions->revokeOthers($user, $currentTokenId);

        if ($revokedCount > 0) {
            AuditLog::log('sessions_revoked', $user, [
                'attributes' => [
                    'reason'        => 'password_changed',
                    'scope'         => 'others',
                    'revoked_count' => $revokedCount,
                ],
            ], $user);
        }

        return response()->json([
            'message'         => 'Password changed successfully.',
            'sessions_revoked' => $revokedCount,
        ]);
    }
}