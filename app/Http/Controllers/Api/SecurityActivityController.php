<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SecurityActivityController extends Controller
{
    /**
     * Security-relevant events surfaced on the user's own activity timeline.
     * Non-security audit events (created/updated/deleted/restored on business models)
     * are filtered out.
     */
    protected const SECURITY_ACTIONS = [
        'login_success',
        'login_failed',
        'logout',
        'password_changed',
        'password_reset',
        'password_reset_requested',
        'session_revoked',
        'sessions_revoked',
        'profile_updated',
        'avatar_updated',
        'avatar_removed',
        // Reserved for Phase 2B — will simply return nothing until then
        'two_factor_setup_started',
        'two_factor_enabled',
        'two_factor_disabled',
        'two_factor_recovery_codes_regenerated',
        'recovery_code_used',
        'two_factor_challenge_failed',
        'suspicious_login_detected',
        'account_temporarily_locked',
    ];

    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min((int) $request->input('per_page', 25), 100));

        $logs = AuditLog::query()
            ->where('user_id', $request->user()->id)
            ->whereIn('action', self::SECURITY_ACTIONS)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage);

        $items = $logs->getCollection()->map(function (AuditLog $log) {
            $changes = $log->changes ?? [];
            $attrs = $changes['attributes'] ?? [];

            return [
                'id'         => $log->id,
                'action'     => $log->action,
                'created_at' => $log->created_at?->toIso8601String(),
                'ip_address' => $log->ip_address,
                'user_agent' => $log->user_agent,
                'context'    => [
                    'browser'       => $attrs['browser'] ?? null,
                    'platform'      => $attrs['platform'] ?? null,
                    'device'        => $attrs['device'] ?? null,
                    'location'      => $attrs['location'] ?? null,
                    'reason'        => $attrs['reason'] ?? null,
                    'scope'         => $attrs['scope'] ?? null,
                    'revoked_count' => $attrs['revoked_count'] ?? null,
                    'new_device'    => $attrs['new_device'] ?? null,
                ],
            ];
        });

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page'    => $logs->lastPage(),
                'per_page'     => $logs->perPage(),
                'total'        => $logs->total(),
            ],
        ]);
    }
}