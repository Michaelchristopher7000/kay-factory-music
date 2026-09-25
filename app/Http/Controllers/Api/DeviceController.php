<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LoginDevice;
use App\Services\SessionRevocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class DeviceController extends Controller
{
    public function __construct(
        private readonly SessionRevocationService $sessions,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $currentTokenId = $request->user()->currentAccessToken()?->id;

        $devices = LoginDevice::where('user_id', $request->user()->id)
            ->orderByDesc('last_seen_at')
            ->get()
            ->map(function (LoginDevice $device) use ($currentTokenId) {
                return [
                    'id'            => $device->id,
                    'browser'       => $device->browser,
                    'platform'      => $device->platform,
                    'device'        => $device->device,
                    'ip_address'    => $device->ip_address,
                    'location'      => $device->location,
                    'first_seen_at' => $device->first_seen_at?->toIso8601String(),
                    'last_seen_at'  => $device->last_seen_at?->toIso8601String(),
                    'is_current'    => $device->token_id === $currentTokenId,
                ];
            });

        return response()->json(['data' => $devices]);
    }

    public function destroy(Request $request, LoginDevice $device): JsonResponse
    {
        if ($device->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        $isCurrent = $device->token_id === $request->user()->currentAccessToken()?->id;

        if ($device->token_id) {
            PersonalAccessToken::where('id', $device->token_id)->delete();
        }

        $device->delete();

        return response()->json([
            'message'     => 'Device revoked successfully.',
            'was_current' => $isCurrent,
        ]);
    }

    /**
     * Revoke every session EXCEPT the one making this request.
     */
    public function revokeOthers(Request $request): JsonResponse
    {
        $user = $request->user();
        $currentTokenId = $user->currentAccessToken()?->id;

        $revokedCount = $this->sessions->revokeOthers($user, $currentTokenId);

        return response()->json([
            'message'       => 'All other sessions have been revoked.',
            'revoked_count' => $revokedCount,
        ]);
    }

    /**
     * Revoke EVERY session, including the current one.
     * The frontend should treat this as a full sign-out.
     */
    public function revokeAll(Request $request): JsonResponse
    {
        $user = $request->user();

        $revokedCount = $this->sessions->revokeAll($user);

        return response()->json([
            'message'       => 'All sessions have been revoked.',
            'revoked_count' => $revokedCount,
            'was_current'   => true,
        ]);
    }
}