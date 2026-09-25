<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TwoFactorRecoveryCodesTest extends TestCase
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

    protected function enableTwoFactor(User $user): array
    {
        $service = app(\App\Services\TwoFactorService::class);
        $bundle = $service->generateRecoveryCodes(8);

        $user->two_factor_secret = $service->generateSecret();
        $user->two_factor_recovery_codes = $bundle['hashed'];
        $user->two_factor_confirmed_at = now();
        $user->save();

        return $bundle['plaintext'];
    }

    public function test_recovery_code_logs_in_successfully(): void
    {
        $user = $this->makeSuperAdmin();
        $codes = $this->enableTwoFactor($user);

        $login = $this->postJson('/api/login', [
            'email' => 'admin@kayfactory.test',
            'password' => 'Password123!',
        ])->assertOk();

        $this->postJson('/api/login/2fa', [
            'challenge_token' => $login->json('challenge_token'),
            'code' => $codes[0],
        ])->assertOk()->assertJsonStructure(['token', 'user']);
    }

    public function test_recovery_code_can_only_be_used_once(): void
    {
        $user = $this->makeSuperAdmin();
        $codes = $this->enableTwoFactor($user);

        // First login with recovery code
        $login1 = $this->postJson('/api/login', [
            'email' => 'admin@kayfactory.test',
            'password' => 'Password123!',
        ])->assertOk();

        $this->postJson('/api/login/2fa', [
            'challenge_token' => $login1->json('challenge_token'),
            'code' => $codes[0],
        ])->assertOk();

        // Second login with same code
        $this->app['auth']->forgetGuards();

        $login2 = $this->postJson('/api/login', [
            'email' => 'admin@kayfactory.test',
            'password' => 'Password123!',
        ])->assertOk();

        $this->postJson('/api/login/2fa', [
            'challenge_token' => $login2->json('challenge_token'),
            'code' => $codes[0],
        ])->assertStatus(422);

        // User's remaining codes should be 7
        $user->refresh();
        $this->assertCount(7, $user->two_factor_recovery_codes);
    }

    public function test_recovery_code_used_audit_event_written(): void
    {
        $user = $this->makeSuperAdmin();
        $codes = $this->enableTwoFactor($user);

        $login = $this->postJson('/api/login', [
            'email' => 'admin@kayfactory.test',
            'password' => 'Password123!',
        ])->assertOk();

        $this->postJson('/api/login/2fa', [
            'challenge_token' => $login->json('challenge_token'),
            'code' => $codes[0],
        ])->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action'  => 'recovery_code_used',
        ]);
    }

    public function test_invalid_recovery_code_rejected(): void
    {
        $user = $this->makeSuperAdmin();
        $this->enableTwoFactor($user);

        $login = $this->postJson('/api/login', [
            'email' => 'admin@kayfactory.test',
            'password' => 'Password123!',
        ])->assertOk();

        $this->postJson('/api/login/2fa', [
            'challenge_token' => $login->json('challenge_token'),
            'code' => 'ZZZZZ-ZZZZZ',
        ])->assertStatus(422);
    }

    public function test_regenerate_recovery_codes_requires_password(): void
    {
        $user = $this->makeSuperAdmin();
        $this->enableTwoFactor($user);
        Sanctum::actingAs($user, ['*'], 'sanctum');

        $this->postJson('/api/me/2fa/recovery-codes', [
            'password' => 'WrongPassword!',
        ])->assertStatus(422);
    }

    public function test_regenerate_recovery_codes_invalidates_old_set(): void
    {
        $user = $this->makeSuperAdmin();
        $oldCodes = $this->enableTwoFactor($user);
        Sanctum::actingAs($user, ['*'], 'sanctum');

        $response = $this->postJson('/api/me/2fa/recovery-codes', [
            'password' => 'Password123!',
        ]);

        $response->assertOk()->assertJsonStructure(['message', 'recovery_codes']);
        $newCodes = $response->json('recovery_codes');

        $this->assertCount(8, $newCodes);
        $this->assertNotSame($oldCodes, $newCodes);

        // Old codes no longer match any stored hash
        $user->refresh();
        $hashes = $user->two_factor_recovery_codes;
        foreach ($oldCodes as $old) {
            foreach ($hashes as $hash) {
                $this->assertFalse(Hash::check($old, $hash));
            }
        }
    }

    public function test_regeneration_audit_event_written(): void
    {
        $user = $this->makeSuperAdmin();
        $this->enableTwoFactor($user);
        Sanctum::actingAs($user, ['*'], 'sanctum');

        $this->postJson('/api/me/2fa/recovery-codes', [
            'password' => 'Password123!',
        ])->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action'  => 'two_factor_recovery_codes_regenerated',
        ]);
    }

    public function test_regenerate_blocked_when_2fa_disabled(): void
    {
        $user = $this->makeSuperAdmin();
        Sanctum::actingAs($user, ['*'], 'sanctum');

        $this->postJson('/api/me/2fa/recovery-codes', [
            'password' => 'Password123!',
        ])->assertStatus(422);
    }
}