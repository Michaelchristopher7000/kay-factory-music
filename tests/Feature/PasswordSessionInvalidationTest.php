<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\LoginDevice;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordSessionInvalidationTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(string $email = 'user@kayfactory.test'): User
    {
        $role = Role::firstOrCreate(
            ['slug' => 'super-admin'],
            ['name' => 'Super Admin']
        );

        return User::create([
            'name' => 'Test User',
            'email' => $email,
            'password' => 'OldPassword123!',
            'role_id' => $role->id,
        ]);
    }

    /**
     * Creates two tokens + two matching login_devices rows for the user.
     * Returns identifiers so tests can reference them.
     */
    protected function setupTwoSessions(User $user): array
    {
        $resultA = $user->createToken('device-a');
        $tokenA = $resultA->plainTextToken;
        $tokenAId = $resultA->accessToken->id;

        $resultB = $user->createToken('device-b');
        $tokenB = $resultB->plainTextToken;
        $tokenBId = $resultB->accessToken->id;

        $deviceA = LoginDevice::create([
            'user_id' => $user->id,
            'device_hash' => hash('sha256', 'a'),
            'token_id' => $tokenAId,
            'browser' => 'Chrome',
            'platform' => 'Windows',
            'device' => 'Desktop',
            'ip_address' => '127.0.0.1',
            'location' => 'Local Network',
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ]);

        $deviceB = LoginDevice::create([
            'user_id' => $user->id,
            'device_hash' => hash('sha256', 'b'),
            'token_id' => $tokenBId,
            'browser' => 'Firefox',
            'platform' => 'macOS',
            'device' => 'Desktop',
            'ip_address' => '127.0.0.1',
            'location' => 'Local Network',
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ]);

        return compact('tokenA', 'tokenB', 'tokenAId', 'tokenBId', 'deviceA', 'deviceB');
    }

    /* ============================================================
       PASSWORD CHANGE — revokes others, keeps current
       ============================================================ */

    public function test_password_change_revokes_other_sessions_keeps_current(): void
    {
        $user = $this->makeUser();
        $ctx = $this->setupTwoSessions($user);

        // Act as device A
        $response = $this->withHeader('Authorization', "Bearer {$ctx['tokenA']}")
            ->postJson('/api/me/password', [
                'current_password' => 'OldPassword123!',
                'password' => 'NewSecurePass123!',
                'password_confirmation' => 'NewSecurePass123!',
            ]);

        $response->assertOk()
            ->assertJsonPath('sessions_revoked', 1);

        // Token B should be gone
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $ctx['tokenBId']]);
        // Token A should still exist
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $ctx['tokenAId']]);

        // Device B row removed, device A kept
        $this->assertDatabaseMissing('login_devices', ['id' => $ctx['deviceB']->id]);
        $this->assertDatabaseHas('login_devices', ['id' => $ctx['deviceA']->id]);
    }

    public function test_password_change_logs_sessions_revoked_with_reason(): void
    {
        $user = $this->makeUser();
        $ctx = $this->setupTwoSessions($user);

        $this->withHeader('Authorization', "Bearer {$ctx['tokenA']}")
            ->postJson('/api/me/password', [
                'current_password' => 'OldPassword123!',
                'password' => 'NewSecurePass123!',
                'password_confirmation' => 'NewSecurePass123!',
            ])->assertOk();

        $log = AuditLog::where('user_id', $user->id)
            ->where('action', 'sessions_revoked')
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('password_changed', $log->changes['attributes']['reason'] ?? null);
        $this->assertSame('others', $log->changes['attributes']['scope'] ?? null);
        $this->assertSame(1, $log->changes['attributes']['revoked_count'] ?? null);
    }

    public function test_password_change_logs_password_changed_event(): void
    {
        $user = $this->makeUser();
        $ctx = $this->setupTwoSessions($user);

        $this->withHeader('Authorization', "Bearer {$ctx['tokenA']}")
            ->postJson('/api/me/password', [
                'current_password' => 'OldPassword123!',
                'password' => 'NewSecurePass123!',
                'password_confirmation' => 'NewSecurePass123!',
            ])->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action'  => 'password_changed',
        ]);
    }

    public function test_revoked_token_returns_401_after_password_change(): void
    {
        $user = $this->makeUser();
        $ctx = $this->setupTwoSessions($user);

        $this->withHeader('Authorization', "Bearer {$ctx['tokenA']}")
            ->postJson('/api/me/password', [
                'current_password' => 'OldPassword123!',
                'password' => 'NewSecurePass123!',
                'password_confirmation' => 'NewSecurePass123!',
            ])->assertOk();

        $this->app['auth']->forgetGuards();

        // Old token B should now be rejected
        $this->withHeader('Authorization', "Bearer {$ctx['tokenB']}")
            ->getJson('/api/me')
            ->assertUnauthorized();

        // Current token A should still work
        $this->withHeader('Authorization', "Bearer {$ctx['tokenA']}")
            ->getJson('/api/me')
            ->assertOk();
    }

    public function test_password_change_with_no_other_sessions_reports_zero(): void
    {
        $user = $this->makeUser();
        // Only one session (the current one)
        $result = $user->createToken('solo');
        $token = $result->plainTextToken;
        $tokenId = $result->accessToken->id;

        LoginDevice::create([
            'user_id' => $user->id,
            'device_hash' => hash('sha256', 'solo'),
            'token_id' => $tokenId,
            'browser' => 'Chrome',
            'platform' => 'Windows',
            'device' => 'Desktop',
            'ip_address' => '127.0.0.1',
            'location' => 'Local Network',
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/me/password', [
                'current_password' => 'OldPassword123!',
                'password' => 'NewSecurePass123!',
                'password_confirmation' => 'NewSecurePass123!',
            ]);

        $response->assertOk()->assertJsonPath('sessions_revoked', 0);

        // No sessions_revoked audit row when nothing was revoked
        $this->assertDatabaseMissing('audit_logs', [
            'user_id' => $user->id,
            'action'  => 'sessions_revoked',
        ]);
    }

    /* ============================================================
       PASSWORD RESET — revokes all sessions
       ============================================================ */

    public function test_password_reset_revokes_all_sessions(): void
    {
        $user = $this->makeUser();
        $ctx = $this->setupTwoSessions($user);

        $resetToken = Password::createToken($user);

        $response = $this->postJson('/api/reset-password', [
            'token' => $resetToken,
            'email' => $user->email,
            'password' => 'NewSecurePass123!',
            'password_confirmation' => 'NewSecurePass123!',
        ]);

        $response->assertOk()
            ->assertJsonPath('sessions_revoked', 2);

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $ctx['tokenAId']]);
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $ctx['tokenBId']]);
        $this->assertDatabaseCount('login_devices', 0);
    }

    public function test_password_reset_logs_password_reset_and_sessions_revoked(): void
    {
        $user = $this->makeUser();
        $this->setupTwoSessions($user);

        $resetToken = Password::createToken($user);

        $this->postJson('/api/reset-password', [
            'token' => $resetToken,
            'email' => $user->email,
            'password' => 'NewSecurePass123!',
            'password_confirmation' => 'NewSecurePass123!',
        ])->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action'  => 'password_reset',
        ]);

        $log = AuditLog::where('user_id', $user->id)
            ->where('action', 'sessions_revoked')
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('password_reset', $log->changes['attributes']['reason'] ?? null);
        $this->assertSame('all', $log->changes['attributes']['scope'] ?? null);
    }

    public function test_revoked_tokens_return_401_after_password_reset(): void
    {
        $user = $this->makeUser();
        $ctx = $this->setupTwoSessions($user);

        $resetToken = Password::createToken($user);

        $this->postJson('/api/reset-password', [
            'token' => $resetToken,
            'email' => $user->email,
            'password' => 'NewSecurePass123!',
            'password_confirmation' => 'NewSecurePass123!',
        ])->assertOk();

        $this->app['auth']->forgetGuards();

        $this->withHeader('Authorization', "Bearer {$ctx['tokenA']}")
            ->getJson('/api/me')
            ->assertUnauthorized();

        $this->app['auth']->forgetGuards();

        $this->withHeader('Authorization', "Bearer {$ctx['tokenB']}")
            ->getJson('/api/me')
            ->assertUnauthorized();
    }

    public function test_user_can_log_in_with_new_password_after_reset(): void
    {
        $user = $this->makeUser();
        $this->setupTwoSessions($user);

        $resetToken = Password::createToken($user);

        $this->postJson('/api/reset-password', [
            'token' => $resetToken,
            'email' => $user->email,
            'password' => 'NewSecurePass123!',
            'password_confirmation' => 'NewSecurePass123!',
        ])->assertOk();

        $this->assertTrue(Hash::check('NewSecurePass123!', $user->fresh()->password));

        $this->app['auth']->forgetGuards();

        // Can log in with new password
        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'NewSecurePass123!',
        ])->assertOk();
    }

    /* ============================================================
       Existing functionality still works
       ============================================================ */

    public function test_regular_login_still_works(): void
    {
        $this->makeUser();

        $this->postJson('/api/login', [
            'email' => 'user@kayfactory.test',
            'password' => 'OldPassword123!',
        ])->assertOk()->assertJsonStructure(['token', 'user']);
    }

    public function test_regular_logout_still_works(): void
    {
        $user = $this->makeUser();
        $result = $user->createToken('solo');
        $token = $result->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/logout')
            ->assertOk();
    }
}