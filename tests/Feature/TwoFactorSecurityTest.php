<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function makeSuperAdmin(): User
    {
        $role = Role::firstOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);

        return User::create([
            'name' => 'Super Admin',
            'email' => 'admin@kayfactory.test',
            'password' => 'Password123!',
            'role_id' => $role->id,
        ]);
    }

    public function test_user_resource_never_exposes_secret_or_codes(): void
    {
        $user = $this->makeSuperAdmin();

        $service = app(TwoFactorService::class);
        $user->two_factor_secret = $service->generateSecret();
        $user->two_factor_recovery_codes = ['$2y$...', '$2y$...'];
        $user->two_factor_confirmed_at = now();
        $user->save();

        Sanctum::actingAs($user, ['*'], 'sanctum');

        $response = $this->getJson('/api/me');
        $response->assertOk();

        $raw = json_encode($response->json());
        $this->assertStringNotContainsString($user->two_factor_secret, $raw);
        $this->assertStringNotContainsString('two_factor_secret', $raw);
        $this->assertStringNotContainsString('two_factor_recovery_codes', $raw);

        // Only the boolean flag is exposed
        $this->assertTrue($response->json('data.two_factor_enabled'));
    }

    public function test_secret_never_appears_in_audit_logs(): void
    {
        $user = $this->makeSuperAdmin();
        Sanctum::actingAs($user, ['*'], 'sanctum');

        $setup = $this->postJson('/api/me/2fa/setup')->assertOk();
        $secret = $setup->json('data.secret');

        $code = app(Google2FA::class)->getCurrentOtp($secret);
        $this->postJson('/api/me/2fa/verify', ['code' => $code])->assertOk();

        $logs = AuditLog::all();
        $raw = $logs->map(fn ($log) => json_encode($log->changes))->implode(' ');

        $this->assertStringNotContainsString($secret, $raw);
        $this->assertStringNotContainsString('two_factor_secret', $raw);
    }

    public function test_recovery_codes_never_appear_in_audit_logs(): void
    {
        $user = $this->makeSuperAdmin();
        Sanctum::actingAs($user, ['*'], 'sanctum');

        $setup = $this->postJson('/api/me/2fa/setup')->assertOk();
        $code = app(Google2FA::class)->getCurrentOtp($setup->json('data.secret'));
        $verify = $this->postJson('/api/me/2fa/verify', ['code' => $code])->assertOk();

        $codes = $verify->json('recovery_codes');

        $logs = AuditLog::all();
        $raw = $logs->map(fn ($log) => json_encode($log->changes))->implode(' ');

        foreach ($codes as $recoveryCode) {
            $this->assertStringNotContainsString($recoveryCode, $raw);
        }
    }

    public function test_challenge_token_never_appears_in_audit_logs(): void
    {
        $user = $this->makeSuperAdmin();
        $service = app(TwoFactorService::class);
        $secret = $service->generateSecret();
        $user->two_factor_secret = $secret;
        $user->two_factor_confirmed_at = now();
        $user->two_factor_recovery_codes = [];
        $user->save();

        $login = $this->postJson('/api/login', [
            'email' => 'admin@kayfactory.test',
            'password' => 'Password123!',
        ])->assertOk();

        $challenge = $login->json('challenge_token');

        // Fail an attempt so it hits the audit event
        $this->postJson('/api/login/2fa', [
            'challenge_token' => $challenge,
            'code' => '000000',
        ]);

        $logs = AuditLog::all();
        $raw = $logs->map(fn ($log) => json_encode($log->changes))->implode(' ');

        $this->assertStringNotContainsString($challenge, $raw);
    }

    public function test_password_never_appears_in_two_factor_audit_logs(): void
    {
        $user = $this->makeSuperAdmin();
        Sanctum::actingAs($user, ['*'], 'sanctum');

        $service = app(TwoFactorService::class);
        $bundle = $service->generateRecoveryCodes(8);
        $user->two_factor_secret = $service->generateSecret();
        $user->two_factor_recovery_codes = $bundle['hashed'];
        $user->two_factor_confirmed_at = now();
        $user->save();

        $this->postJson('/api/me/2fa/disable', ['password' => 'Password123!'])->assertOk();

        $logs = AuditLog::all();
        $raw = $logs->map(fn ($log) => json_encode($log->changes))->implode(' ');

        $this->assertStringNotContainsString('Password123!', $raw);
        $this->assertStringNotContainsString('password', strtolower($raw));
    }
}

