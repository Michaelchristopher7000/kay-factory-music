<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(string $email, string $roleSlug = 'super-admin'): User
    {
        $role = Role::firstOrCreate(['slug' => $roleSlug], ['name' => ucfirst($roleSlug)]);

        return User::create([
            'name' => 'Test User',
            'email' => $email,
            'password' => 'Password123!',
            'role_id' => $role->id,
        ]);
    }

    protected function enableTwoFactor(User $user): string
    {
        $secret = app(\App\Services\TwoFactorService::class)->generateSecret();
        $user->two_factor_secret = $secret;
        $user->two_factor_confirmed_at = now();
        $user->two_factor_recovery_codes = [];
        $user->save();

        return $secret;
    }

    public function test_user_without_2fa_logs_in_normally(): void
    {
        $this->makeUser('plain@kayfactory.test', 'label-manager');

        $this->postJson('/api/login', [
            'email' => 'plain@kayfactory.test',
            'password' => 'Password123!',
        ])->assertOk()->assertJsonStructure(['token', 'user']);
    }

    public function test_super_admin_without_2fa_enabled_logs_in_normally(): void
    {
        $this->makeUser('sa@kayfactory.test');

        $this->postJson('/api/login', [
            'email' => 'sa@kayfactory.test',
            'password' => 'Password123!',
        ])->assertOk()->assertJsonStructure(['token', 'user']);
    }

    public function test_enabled_2fa_returns_challenge_token_instead_of_sanctum_token(): void
    {
        $user = $this->makeUser('sa2fa@kayfactory.test');
        $this->enableTwoFactor($user);

        $response = $this->postJson('/api/login', [
            'email' => 'sa2fa@kayfactory.test',
            'password' => 'Password123!',
        ]);

        $response->assertOk()
            ->assertJson(['requires_2fa' => true])
            ->assertJsonStructure(['challenge_token'])
            ->assertJsonMissing(['token']); // no Sanctum token issued

        $this->assertNotEmpty($response->json('challenge_token'));
    }

    public function test_valid_totp_issues_token(): void
    {
        $user = $this->makeUser('sa2fa@kayfactory.test');
        $secret = $this->enableTwoFactor($user);

        $login = $this->postJson('/api/login', [
            'email' => 'sa2fa@kayfactory.test',
            'password' => 'Password123!',
        ])->assertOk();

        $challenge = $login->json('challenge_token');
        $code = app(Google2FA::class)->getCurrentOtp($secret);

        $this->postJson('/api/login/2fa', [
            'challenge_token' => $challenge,
            'code' => $code,
        ])->assertOk()->assertJsonStructure(['token', 'user']);
    }

    public function test_invalid_totp_rejected(): void
    {
        $user = $this->makeUser('sa2fa@kayfactory.test');
        $this->enableTwoFactor($user);

        $login = $this->postJson('/api/login', [
            'email' => 'sa2fa@kayfactory.test',
            'password' => 'Password123!',
        ])->assertOk();

        $this->postJson('/api/login/2fa', [
            'challenge_token' => $login->json('challenge_token'),
            'code' => '000000',
        ])->assertStatus(422)->assertJsonValidationErrors(['code']);
    }

    public function test_expired_challenge_rejected(): void
    {
        $user = $this->makeUser('sa2fa@kayfactory.test');
        $secret = $this->enableTwoFactor($user);

        $login = $this->postJson('/api/login', [
            'email' => 'sa2fa@kayfactory.test',
            'password' => 'Password123!',
        ])->assertOk();

        $challenge = $login->json('challenge_token');

        // Simulate expiry
        Cache::forget('2fa_challenge:' . $challenge);

        $code = app(Google2FA::class)->getCurrentOtp($secret);

        $this->postJson('/api/login/2fa', [
            'challenge_token' => $challenge,
            'code' => $code,
        ])->assertStatus(422)
          ->assertJsonPath('message', 'The verification code is invalid or has expired.');
    }

    public function test_challenge_cannot_be_reused(): void
    {
        $user = $this->makeUser('sa2fa@kayfactory.test');
        $secret = $this->enableTwoFactor($user);

        $login = $this->postJson('/api/login', [
            'email' => 'sa2fa@kayfactory.test',
            'password' => 'Password123!',
        ])->assertOk();

        $challenge = $login->json('challenge_token');
        $code = app(Google2FA::class)->getCurrentOtp($secret);

        // First attempt succeeds
        $this->postJson('/api/login/2fa', [
            'challenge_token' => $challenge,
            'code' => $code,
        ])->assertOk();

        // Second attempt with the SAME challenge fails
        $this->app['auth']->forgetGuards();

        $this->postJson('/api/login/2fa', [
            'challenge_token' => $challenge,
            'code' => $code,
        ])->assertStatus(422);
    }

    public function test_rate_limiting_after_repeated_failures(): void
    {
        config()->set('kfm.two_factor.max_verify_attempts', 3);

        $user = $this->makeUser('sa2fa@kayfactory.test');
        $this->enableTwoFactor($user);

        $login = $this->postJson('/api/login', [
            'email' => 'sa2fa@kayfactory.test',
            'password' => 'Password123!',
        ])->assertOk();

        $challenge = $login->json('challenge_token');

        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/login/2fa', [
                'challenge_token' => $challenge,
                'code' => '000000',
            ])->assertStatus(422);
        }

        // 4th attempt is rate-limited
        $this->postJson('/api/login/2fa', [
            'challenge_token' => $challenge,
            'code' => '000000',
        ])->assertStatus(422);
    }
}