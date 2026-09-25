<?php

namespace App\Http\Requests\Auth;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('email', 'password'))) {
            RateLimiter::hit($this->emailKey(), $this->decaySeconds());
            RateLimiter::hit($this->ipKey(), $this->decaySeconds());

            $this->logFailedAttempt();

            throw ValidationException::withMessages([
                'email' => 'Invalid credentials.',
            ]);
        }

        RateLimiter::clear($this->emailKey());
    }

    /**
     * Log the failed attempt against the target account, but only if it exists.
     * This preserves the no-enumeration guarantee: the API response is identical
     * whether or not the account exists.
     */
    protected function logFailedAttempt(): void
    {
        $email = $this->input('email');
        if (! is_string($email) || $email === '') {
            return;
        }

        $targetUser = User::where('email', $email)->first();
        if (! $targetUser) {
            return;
        }

        AuditLog::log('login_failed', $targetUser, [
            'attributes' => [
                'reason' => 'invalid_credentials',
            ],
        ], $targetUser);
    }

    protected function ensureIsNotRateLimited(): void
    {
        $emailLimited = RateLimiter::tooManyAttempts($this->emailKey(), $this->maxAttempts());
        $ipLimited    = RateLimiter::tooManyAttempts($this->ipKey(), $this->ipMaxAttempts());

        if (! $emailLimited && ! $ipLimited) {
            return;
        }

        $seconds = (int) max(
            RateLimiter::availableIn($this->emailKey()),
            RateLimiter::availableIn($this->ipKey()),
        );

        $minutes = max(1, (int) ceil($seconds / 60));

        throw new ThrottleRequestsException(
            "Too many login attempts. Please try again in {$minutes} minute" . ($minutes > 1 ? 's' : '') . '.',
            null,
            ['Retry-After' => $seconds]
        );
    }

    protected function emailKey(): string
    {
        return 'login-email:' . Str::lower((string) $this->input('email'));
    }

    protected function ipKey(): string
    {
        return 'login-ip:' . $this->ip();
    }

    protected function maxAttempts(): int
    {
        return (int) config('kfm.login.max_attempts', 5);
    }

    protected function ipMaxAttempts(): int
    {
        return (int) config('kfm.login.ip_max_attempts', 20);
    }

    protected function decaySeconds(): int
    {
        return (int) config('kfm.login.decay_seconds', 900);
    }
}