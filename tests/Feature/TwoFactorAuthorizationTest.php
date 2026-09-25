<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TwoFactorAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(string $email, string $roleSlug): User
    {
        $role = Role::firstOrCreate(['slug' => $roleSlug], ['name' => ucfirst(str_replace('-', ' ', $roleSlug))]);

        return User::create([
            'name' => 'Test',
            'email' => $email,
            'password' => 'Password123!',
            'role_id' => $role->id,
        ]);
    }

    public function test_label_manager_cannot_start_setup(): void
    {
        $user = $this->makeUser('lm@kayfactory.test', 'label-manager');
        Sanctum::actingAs($user, ['*'], 'sanctum');

        $this->postJson('/api/me/2fa/setup')->assertForbidden();
    }

    public function test_label_manager_cannot_verify(): void
    {
        $user = $this->makeUser('lm@kayfactory.test', 'label-manager');
        Sanctum::actingAs($user, ['*'], 'sanctum');

        $this->postJson('/api/me/2fa/verify', ['code' => '123456'])->assertForbidden();
    }

    public function test_label_manager_cannot_disable(): void
    {
        $user = $this->makeUser('lm@kayfactory.test', 'label-manager');
        Sanctum::actingAs($user, ['*'], 'sanctum');

        $this->postJson('/api/me/2fa/disable', ['password' => 'Password123!'])->assertForbidden();
    }

    public function test_label_manager_cannot_regenerate_recovery_codes(): void
    {
        $user = $this->makeUser('lm@kayfactory.test', 'label-manager');
        Sanctum::actingAs($user, ['*'], 'sanctum');

        $this->postJson('/api/me/2fa/recovery-codes', ['password' => 'Password123!'])->assertForbidden();
    }

    public function test_unauthenticated_requests_rejected(): void
    {
        $this->postJson('/api/me/2fa/setup')->assertUnauthorized();
        $this->postJson('/api/me/2fa/verify', ['code' => '123456'])->assertUnauthorized();
        $this->postJson('/api/me/2fa/disable', ['password' => 'x'])->assertUnauthorized();
        $this->postJson('/api/me/2fa/recovery-codes', ['password' => 'x'])->assertUnauthorized();
    }
}