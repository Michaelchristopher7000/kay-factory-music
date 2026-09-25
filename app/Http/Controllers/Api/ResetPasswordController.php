<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\AuditLog;
use App\Services\SessionRevocationService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class ResetPasswordController extends Controller
{
    public function __construct(
        private readonly SessionRevocationService $sessions,
    ) {}

    /**
     * Reset the user's password using a valid reset token.
     * Revokes ALL existing sessions for the user.
     */
    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        $revokedCount = 0;
        $resetUser = null;

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) use (&$revokedCount, &$resetUser) {
                // The User model has a `hashed` cast on password.
                $user->forceFill([
                    'password' => $password,
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));

                // Revoke ALL sessions (user is not authenticated during reset)
                $revokedCount = $this->sessions->revokeAll($user);

                AuditLog::log('password_reset', $user, [
                    'attributes' => [
                        'reset' => true,
                        'at'    => now()->toIso8601String(),
                    ],
                ], $user);

                if ($revokedCount > 0) {
                    AuditLog::log('sessions_revoked', $user, [
                        'attributes' => [
                            'reason'        => 'password_reset',
                            'scope'         => 'all',
                            'revoked_count' => $revokedCount,
                        ],
                    ], $user);
                }

                $resetUser = $user;
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json([
                'message' => 'This password reset link is invalid or has expired.',
            ], 422);
        }

        return response()->json([
            'message'          => 'Your password has been reset. You can now sign in with your new password.',
            'sessions_revoked' => $revokedCount,
        ]);
    }
}