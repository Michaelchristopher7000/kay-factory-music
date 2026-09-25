<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    /**
     * Send a password reset link to the given email.
     * Response is intentionally generic to prevent account enumeration.
     */
    public function sendResetLink(ForgotPasswordRequest $request): JsonResponse
    {
        $email = $request->input('email');

        Password::sendResetLink($request->only('email'));

        // Log only if the account exists — otherwise the audit row would be orphaned
        // and the log entry could theoretically leak existence.
        $targetUser = User::where('email', $email)->first();
        if ($targetUser) {
            AuditLog::log('password_reset_requested', $targetUser, [
                'attributes' => [
                    'requested_at' => now()->toIso8601String(),
                ],
            ], $targetUser);
        }

        return response()->json([
            'message' => 'If an account exists for that email address, a password reset link has been sent.',
        ]);
    }
}