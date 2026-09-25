<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\DisableTwoFactorRequest;
use App\Http\Requests\Profile\RegenerateRecoveryCodesRequest;
use App\Models\AuditLog;
use App\Services\SessionRevocationService;
use App\Services\TwoFactorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TwoFactorController extends Controller
{
    public function __construct(
        private readonly TwoFactorService $twoFactor,
        private readonly SessionRevocationService $sessions,
    ) {}

    /**
     * POST /api/me/2fa/setup
     *
     * Starts (or restarts, if unconfirmed) a 2FA enrollment.
     * Returns the provisioning secret + QR code.
     * Refuses to replace an already-confirmed secret.
     */
    public function setup(Request $request): JsonResponse
    {
        $user = $request->user();

        // Refuse to overwrite an already-confirmed 2FA configuration.
        if ($user->hasTwoFactorEnabled()) {
            return response()->json([
                'message' => 'Two-factor authentication is already enabled. Disable it first to re-enroll.',
            ], 422);
        }

        // Generate a fresh secret. An unconfirmed prior setup is safely discarded.
        $secret = $this->twoFactor->generateSecret();

        $user->two_factor_secret = $secret;
        $user->two_factor_recovery_codes = null;
        $user->two_factor_confirmed_at = null;
        $user->save();

        AuditLog::log('two_factor_setup_started', $user, [
            'attributes' => [
                'at' => now()->toIso8601String(),
            ],
        ], $user);

        return response()->json([
            'data' => [
                'secret'      => $secret,
                'qr_code'     => $this->twoFactor->qrCodeDataUri($user->email, $secret),
                'otpauth_url' => $this->twoFactor->qrCodeUrl($user->email, $secret),
            ],
        ]);
    }

    /**
     * POST /api/me/2fa/verify
     *
     * Confirms setup using the first TOTP code from the authenticator.
     * On success: 2FA becomes active + 8 recovery codes are returned ONCE.
     */
    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'min:6', 'max:20'],
        ]);

        $user = $request->user();

        if ($user->two_factor_secret === null) {
            throw ValidationException::withMessages([
                'code' => 'Two-factor setup has not been started.',
            ]);
        }

        if ($user->two_factor_confirmed_at !== null) {
            throw ValidationException::withMessages([
                'code' => 'Two-factor authentication is already enabled.',
            ]);
        }

        $code = (string) $request->input('code');

        if (! $this->twoFactor->verifyCode($user->two_factor_secret, $code)) {
            throw ValidationException::withMessages([
                'code' => 'The verification code is invalid or has expired.',
            ]);
        }

        // Activate 2FA + generate recovery codes
        $bundle = $this->twoFactor->generateRecoveryCodes(
            (int) config('kfm.two_factor.recovery_code_count', 8)
        );

        $user->two_factor_recovery_codes = $bundle['hashed'];
        $user->two_factor_confirmed_at = now();
        $user->save();

        AuditLog::log('two_factor_enabled', $user, [
            'attributes' => [
                'recovery_codes_generated' => count($bundle['hashed']),
                'at' => now()->toIso8601String(),
            ],
        ], $user);

        return response()->json([
            'message'        => 'Two-factor authentication is now enabled.',
            'recovery_codes' => $bundle['plaintext'],
        ]);
    }

    /**
     * POST /api/me/2fa/disable
     *
     * Disables 2FA. Requires current password.
     * Revokes all OTHER sessions (keeps the current one alive).
     */
    public function disable(DisableTwoFactorRequest $request): JsonResponse
    {
        $user = $request->user();
        $currentTokenId = $user->currentAccessToken()?->id;

        $user->two_factor_secret = null;
        $user->two_factor_recovery_codes = null;
        $user->two_factor_confirmed_at = null;
        $user->save();

        AuditLog::log('two_factor_disabled', $user, [
            'attributes' => [
                'at' => now()->toIso8601String(),
            ],
        ], $user);

        // Revoke every OTHER session
        $revokedCount = $this->sessions->revokeOthers($user, $currentTokenId);

        if ($revokedCount > 0) {
            AuditLog::log('sessions_revoked', $user, [
                'attributes' => [
                    'reason'        => 'two_factor_disabled',
                    'scope'         => 'others',
                    'revoked_count' => $revokedCount,
                ],
            ], $user);
        }

        return response()->json([
            'message'          => 'Two-factor authentication has been disabled.',
            'sessions_revoked' => $revokedCount,
        ]);
    }

    /**
     * POST /api/me/2fa/recovery-codes
     *
     * Regenerates the recovery-code set. Requires current password + active 2FA.
     * The new set invalidates the previous one.
     */
    public function regenerateRecoveryCodes(RegenerateRecoveryCodesRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->hasTwoFactorEnabled()) {
            return response()->json([
                'message' => 'Two-factor authentication is not enabled.',
            ], 422);
        }

        $bundle = $this->twoFactor->generateRecoveryCodes(
            (int) config('kfm.two_factor.recovery_code_count', 8)
        );

        $user->two_factor_recovery_codes = $bundle['hashed'];
        $user->save();

        AuditLog::log('two_factor_recovery_codes_regenerated', $user, [
            'attributes' => [
                'count' => count($bundle['hashed']),
                'at'    => now()->toIso8601String(),
            ],
        ], $user);

        return response()->json([
            'message'        => 'Recovery codes regenerated. Previous codes are no longer valid.',
            'recovery_codes' => $bundle['plaintext'],
        ]);
    }
}