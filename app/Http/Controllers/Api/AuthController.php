<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\TwoFactorChallengeRequest;
use App\Http\Resources\UserResource;
use App\Models\AuditLog;
use App\Models\LoginDevice;
use App\Models\User;
use App\Notifications\LoginNotification;
use App\Notifications\SuspiciousLoginNotification;
use App\Services\TwoFactorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    protected const CHALLENGE_PREFIX = '2fa_challenge:';
    protected const CHALLENGE_ATTEMPT_PREFIX = '2fa-challenge-attempt:';
    protected const CHALLENGE_IP_PREFIX = '2fa-ip:';

    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role_id'  => $request->role_id,
        ]);

        return response()->json([
            'message' => 'User created successfully.',
            'user'    => new UserResource($user->load('role')),
        ], 201);
    }

    public function login(LoginRequest $request)
    {
        $request->authenticate();

        $user = User::where('email', $request->email)->firstOrFail();

        if ($user->hasTwoFactorEnabled()) {
            return $this->startTwoFactorChallenge($user);
        }

        return $this->finalizeLogin($user, $request, usedTwoFactor: false);
    }

    public function loginTwoFactor(TwoFactorChallengeRequest $request): JsonResponse
    {
        $challengeToken = (string) $request->input('challenge_token');
        $code = (string) $request->input('code');

        $this->ensure2faNotRateLimited($challengeToken, $request);

        $cacheKey = self::CHALLENGE_PREFIX . $challengeToken;
        $userId = Cache::get($cacheKey);

        if (! $userId) {
            throw ValidationException::withMessages([
                'code' => 'The verification code is invalid or has expired.',
            ]);
        }

        $user = User::find($userId);

        if (! $user || ! $user->hasTwoFactorEnabled()) {
            Cache::forget($cacheKey);
            throw ValidationException::withMessages([
                'code' => 'The verification code is invalid or has expired.',
            ]);
        }

        /** @var TwoFactorService $twoFactor */
        $twoFactor = app(TwoFactorService::class);

        $verified = false;
        $usedRecoveryCode = false;

        if ($twoFactor->verifyCode((string) $user->two_factor_secret, $code)) {
            $verified = true;
        } else {
            $hashes = is_array($user->two_factor_recovery_codes)
                ? $user->two_factor_recovery_codes
                : [];

            $matchIndex = $twoFactor->findMatchingRecoveryCode($hashes, $code);

            if ($matchIndex !== null) {
                $verified = true;
                $usedRecoveryCode = true;

                unset($hashes[$matchIndex]);
                $user->two_factor_recovery_codes = array_values($hashes);
                $user->save();

                AuditLog::log('recovery_code_used', $user, [
                    'attributes' => [
                        'remaining' => count($user->two_factor_recovery_codes),
                        'at'        => now()->toIso8601String(),
                    ],
                ], $user);
            }
        }

        if (! $verified) {
            $this->record2faFailure($challengeToken, $request, $user);

            throw ValidationException::withMessages([
                'code' => 'The verification code is invalid or has expired.',
            ]);
        }

        Cache::forget($cacheKey);
        RateLimiter::clear(self::CHALLENGE_ATTEMPT_PREFIX . $challengeToken);

        return $this->finalizeLogin($user, $request, usedTwoFactor: true);
    }

    public function logout()
    {
        $user = request()->user();
        $currentToken = $user->currentAccessToken();

        if (! $currentToken) {
            return response()->json(['message' => 'Logged out successfully.']);
        }

        AuditLog::log('logout', $user, [
            'attributes' => [
                'token_id' => $currentToken->id,
            ],
        ], $user);

        LoginDevice::where('user_id', $user->id)
            ->where('token_id', $currentToken->id)
            ->delete();

        $currentToken->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    public function me()
    {
        return new UserResource(request()->user()->load('role'));
    }

    /* ============================================================
       LOGIN — SHARED FINALIZATION
       ============================================================ */

      protected function finalizeLogin(User $user, Request $request, bool $usedTwoFactor = false): JsonResponse
    {
        $token = $user->createToken('auth-token')->plainTextToken;
        $tokenId = $user->tokens()->latest()->first()?->id;

        $userAgent = $request->userAgent() ?? '';
        [$browser, $platform, $device] = $this->parseUserAgent($userAgent);

        $ip = $request->ip() ?? 'Unknown';
        $deviceHash = LoginDevice::generateHash($ip, $browser, $platform);

        $geo = $this->resolveLocation($ip);
        $location = $geo['location'];
        $countryCode = $geo['country_code'];

        $existingDevice = LoginDevice::where('user_id', $user->id)
            ->where('device_hash', $deviceHash)
            ->first();

        // --- Suspicious-login detection ---
        // Runs BEFORE we write/update the current device row so the check
        // compares against the user's prior country history only.
        [$isSuspicious, $knownCountries] = $this->detectSuspiciousLogin($user, $countryCode);

        $isNewDevice = $existingDevice === null;

        if ($existingDevice) {
            $existingDevice->update([
                'last_seen_at' => now(),
                'ip_address'   => $ip,
                'token_id'     => $tokenId,
                'country_code' => $countryCode ?? $existingDevice->country_code,
            ]);
        } else {
            LoginDevice::create([
                'user_id'       => $user->id,
                'device_hash'   => $deviceHash,
                'token_id'      => $tokenId,
                'ip_address'    => $ip,
                'browser'       => $browser,
                'platform'      => $platform,
                'device'        => $device,
                'location'      => $location,
                'country_code'  => $countryCode,
                'first_seen_at' => now(),
                'last_seen_at'  => now(),
            ]);
        }

        // --- Notification branching (mutually exclusive) ---
        $loginTime = now()
            ->timezone(config('app.timezone', 'Africa/Lagos'))
            ->format('M d, Y \a\t h:i A');

        if ($isSuspicious) {
            // Higher-severity alert — do NOT also fire LoginNotification.
            $user->notify(new \App\Notifications\SuspiciousLoginNotification(
                ipAddress: $ip,
                browser: $browser,
                platform: $platform,
                device: $device,
                loginTime: $loginTime,
                location: $location,
                countryCode: $countryCode,
            ));

            AuditLog::log('suspicious_login_detected', $user, [
                'attributes' => [
                    'browser'          => $browser,
                    'platform'         => $platform,
                    'device'           => $device,
                    'ip_address'       => $ip,
                    'location'         => $location,
                    'country_code'     => $countryCode,
                    'known_countries'  => $knownCountries,
                    'new_device'       => $isNewDevice,
                ],
            ], $user);
        } elseif ($isNewDevice) {
            // Standard new-device notification (existing Phase 1 behavior).
            $user->notify(new LoginNotification(
                ipAddress: $ip,
                browser: $browser,
                platform: $platform,
                device: $device,
                loginTime: $loginTime,
                location: $location,
            ));
        }
        // else: known device + known country → no notification

        AuditLog::log('login_success', $user, [
            'attributes' => [
                'browser'      => $browser,
                'platform'     => $platform,
                'device'       => $device,
                'ip_address'   => $ip,
                'new_device'   => $isNewDevice,
                'two_factor'   => $usedTwoFactor,
                'country_code' => $countryCode,
            ],
        ], $user);

        return response()->json([
            'user'  => new UserResource($user->load('role')),
            'token' => $token,
        ]);
    }

    /**
     * Decide whether this login should trigger a suspicious-login alert.
     *
     * Rules:
     *  - Null country code (private IP, resolver failure, or unknown) → not suspicious.
     *  - User has no prior country history → not suspicious (first-ever login).
     *  - Current country is already in the user's known set → not suspicious.
     *  - Otherwise → suspicious.
     *
     * @return array{0: bool, 1: array<int, string>}  [isSuspicious, knownCountries]
     */
    protected function detectSuspiciousLogin(User $user, ?string $countryCode): array
    {
        $knownCountries = LoginDevice::where('user_id', $user->id)
            ->whereNotNull('country_code')
            ->distinct()
            ->pluck('country_code')
            ->values()
            ->all();

        if ($countryCode === null || empty($knownCountries)) {
            return [false, $knownCountries];
        }

        $isSuspicious = ! in_array($countryCode, $knownCountries, true);

        return [$isSuspicious, $knownCountries];
    }

    /* ============================================================
       2FA CHALLENGE HELPERS
       ============================================================ */

    protected function startTwoFactorChallenge(User $user): JsonResponse
    {
        $challengeToken = Str::random(64);

        Cache::put(
            self::CHALLENGE_PREFIX . $challengeToken,
            $user->id,
            now()->addSeconds((int) config('kfm.two_factor.challenge_ttl_seconds', 300))
        );

        return response()->json([
            'requires_2fa'    => true,
            'challenge_token' => $challengeToken,
        ]);
    }

    protected function ensure2faNotRateLimited(string $challengeToken, Request $request): void
    {
        $challengeKey = self::CHALLENGE_ATTEMPT_PREFIX . $challengeToken;
        $ipKey = self::CHALLENGE_IP_PREFIX . $request->ip();

        $challengeLimited = RateLimiter::tooManyAttempts(
            $challengeKey,
            (int) config('kfm.two_factor.max_verify_attempts', 5)
        );

        $ipLimited = RateLimiter::tooManyAttempts(
            $ipKey,
            (int) config('kfm.two_factor.ip_max_attempts', 10)
        );

        if (! $challengeLimited && ! $ipLimited) {
            return;
        }

        $seconds = (int) max(
            RateLimiter::availableIn($challengeKey),
            RateLimiter::availableIn($ipKey),
        );

        $minutes = max(1, (int) ceil($seconds / 60));

        throw ValidationException::withMessages([
            'code' => "Too many verification attempts. Please try again in {$minutes} minute"
                . ($minutes > 1 ? 's' : '') . '.',
        ]);
    }

    protected function record2faFailure(string $challengeToken, Request $request, User $user): void
    {
        $decay = (int) config('kfm.two_factor.decay_seconds', 900);

        RateLimiter::hit(self::CHALLENGE_ATTEMPT_PREFIX . $challengeToken, $decay);
        RateLimiter::hit(self::CHALLENGE_IP_PREFIX . $request->ip(), $decay);

        AuditLog::log('two_factor_challenge_failed', $user, [
            'attributes' => [
                'at' => now()->toIso8601String(),
            ],
        ], $user);
    }

    /* ============================================================
       PRIVATE HELPERS
       ============================================================ */

    private function parseUserAgent(string $ua): array
    {
        $browser = 'Unknown Browser';
        if (preg_match('/Edg\/([\d.]+)/', $ua))            $browser = 'Microsoft Edge';
        elseif (preg_match('/OPR\/([\d.]+)/', $ua))        $browser = 'Opera';
        elseif (preg_match('/Chrome\/([\d.]+)/', $ua))     $browser = 'Chrome';
        elseif (preg_match('/Firefox\/([\d.]+)/', $ua))    $browser = 'Firefox';
        elseif (preg_match('/Safari\/([\d.]+)/', $ua))     $browser = 'Safari';

        $platform = 'Unknown Platform';
        if (preg_match('/Windows NT 10/', $ua))            $platform = 'Windows 10/11';
        elseif (preg_match('/Windows NT/', $ua))           $platform = 'Windows';
        elseif (preg_match('/Mac OS X/', $ua))             $platform = 'macOS';
        elseif (preg_match('/Android/', $ua))              $platform = 'Android';
        elseif (preg_match('/(iPhone|iPad)/', $ua))        $platform = 'iOS';
        elseif (preg_match('/Linux/', $ua))                $platform = 'Linux';

        $device = 'Desktop';
        if (preg_match('/iPad|Tablet/', $ua))              $device = 'Tablet';
        elseif (preg_match('/Mobile|Android|iPhone/', $ua)) $device = 'Mobile';

        return [$browser, $platform, $device];
    }

    /**
     * Resolve an IP to a location + ISO-3166-1 alpha-2 country code.
     *
     * Always returns an array with both keys. Never throws.
     * On any failure (private IP, network error, invalid response) the
     * values are null — callers must handle null safely.
     *
     * @return array{location: ?string, country_code: ?string}
     */
    private function resolveLocation(?string $ip): array
    {
        $empty = ['location' => null, 'country_code' => null];

        if (
            !$ip
            || in_array($ip, ['127.0.0.1', '::1'], true)
            || str_starts_with($ip, '192.168.')
            || str_starts_with($ip, '10.')
            || str_starts_with($ip, '172.')
        ) {
            return [
                'location'     => 'Local Network',
                'country_code' => null,
            ];
        }

        try {
            $res = Http::timeout(3)->get("https://ipapi.co/{$ip}/json/");

            if (! $res->successful()) {
                return $empty;
            }

            $data = $res->json();

            $parts = array_filter([
                $data['city'] ?? null,
                $data['region'] ?? null,
                $data['country_name'] ?? null,
            ]);

            $location = $parts ? implode(', ', $parts) : null;

            $countryCode = null;
            if (isset($data['country_code']) && is_string($data['country_code'])) {
                $candidate = strtoupper(trim($data['country_code']));
                if (preg_match('/^[A-Z]{2}$/', $candidate)) {
                    $countryCode = $candidate;
                }
            }

            return [
                'location'     => $location,
                'country_code' => $countryCode,
            ];
        } catch (\Throwable $e) {
            // Silent fail — never block or fail authentication because
            // of a geolocation lookup.
            return $empty;
        }
    }
}