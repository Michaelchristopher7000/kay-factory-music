<?php

namespace Tests\Feature;

use App\Models\LoginDevice;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TwoFactorDisableTest extends TestCase
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

    protected function enableTwoFactor(User $user): void
    {
        $service = app(\App\Services\TwoFactorService::class);
        $bundle = $service->generateRecoveryCodes(8);

        $user->two_factor_secret = $service->generateSecret();
        $user->two_factor_recovery_codes = $bundle['hashed'];
        $user->two_factor_confirmed_at = now();
        $user->save();
    }

    protected function setupTwoSessions(User $user): array
    {
        $resultA = $user->createToken('device-a');
        $resultB = $user->createToken('device-b');

        LoginDevice::create([
            'user_id' => $user->id,
            'device_hash' => hash('sha256', 'a'),
            'token_id' => $resultA->accessToken->id,
            'browser' => 'Chrome', 'platform' => 'Windows', 'device' => 'Desktop',
            'ip_address' => '127.0.0.1', 'location' => 'Local Network',
            'first_seen_at' => now(), 'last_seen_at' => now(),
        ]);

        LoginDevice::create([
            'user_id' => $user->id,
            'device_hash' => hash('sha256', 'b'),
            'token_id' => $resultB->accessToken->id,
            'browser' => 'Firefox', 'platform' => 'macOS', 'device' => 'Desktop',
            'ip_address' => '127.0.0.1', 'location' => 'Local Network',
            'first_seen_at' => now(), 'last_seen_at' => now(),
        ]);

        return [
            'tokenA' => $resultA->plainTextToken,
            'tokenB' => $resultB->plainTextToken,
            'tokenAId' => $resultA->accessToken->id,
            'tokenBId' => $resultB->accessToken->id,
        ];
    }

    public function test_disable_requires_correct_password(): void
    {
        $user = $this->makeSuperAdmin();
        $this->enableTwoFactor($user);
        $ctx = $this->setupTwoSessions($user);

        $this->withHeader('Authorization', "Bearer {$ctx['tokenA']}")
            ->postJson('/api/me/2fa/disable', ['password' => 'WrongPassword!'])
            ->assertStatus(422);

        $user->refresh();
        $this->assertTrue($user->hasTwoFactorEnabled());
    }

    public function test_disable_clears_2fa_state(): void
    {
        $user = $this->makeSuperAdmin();
        $this->enableTwoFactor($user);
        $ctx = $this->setupTwoSessions($user);

        $this->withHeader('Authorization', "Bearer {$ctx['tokenA']}")
            ->postJson('/api/me/2fa/disable', ['password' => 'Password123!'])
            ->assertOk();

        $user->refresh();
        $this->assertNull($user->two_factor_secret);
        $this->assertNull($user->two_factor_recovery_codes);
        $this->assertNull($user->two_factor_confirmed_at);
        $this->assertFalse($user->hasTwoFactorEnabled());
    }

    public function test_disable_revokes_other_sessions_keeps_current(): void
    {
        $user = $this->makeSuperAdmin();
        $this->enableTwoFactor($user);
        $ctx = $this->setupTwoSessions($user);

        $response = $this->withHeader('Authorization', "Bearer {$ctx['tokenA']}")
            ->postJson('/api/me/2fa/disable', ['password' => 'Password123!'])
            ->assertOk();

        $response->assertJsonPath('sessions_revoked', 1);

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $ctx['tokenBId']]);
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $ctx['tokenAId']]);
    }

    public function test_disable_writes_audit_events(): void
    {
        $user = $this->makeSuperAdmin();
        $this->enableTwoFactor($user);
        $ctx = $this->setupTwoSessions($user);

        $this->withHeader('Authorization', "Bearer {$ctx['tokenA']}")
            ->postJson('/api/me/2fa/disable', ['password' => 'Password123!'])
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action'  => 'two_factor_disabled',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action'  => 'sessions_revoked',
        ]);
    }
}