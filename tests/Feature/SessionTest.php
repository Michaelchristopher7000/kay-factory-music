<?php

namespace Tests\Feature;

use App\Models\LoginDevice;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SessionTest extends TestCase
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
            'password' => 'Password123!',
            'role_id' => $role->id,
        ]);
    }

    protected function setupTwoDevices(User $user): array
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
            'browser' => 'Chrome', 'platform' => 'Windows', 'device' => 'Desktop',
            'ip_address' => '127.0.0.1', 'location' => 'Local Network',
            'first_seen_at' => now(), 'last_seen_at' => now(),
        ]);

        $deviceB = LoginDevice::create([
            'user_id' => $user->id,
            'device_hash' => hash('sha256', 'b'),
            'token_id' => $tokenBId,
            'browser' => 'Firefox', 'platform' => 'macOS', 'device' => 'Desktop',
            'ip_address' => '127.0.0.1', 'location' => 'Local Network',
            'first_seen_at' => now(), 'last_seen_at' => now(),
        ]);

        return compact('tokenA', 'tokenB', 'tokenAId', 'tokenBId', 'deviceA', 'deviceB');
    }

    public function test_list_own_sessions(): void
    {
        $user = $this->makeUser();
        $this->setupTwoDevices($user);
        Sanctum::actingAs($user, ['*'], 'sanctum');

        $this->getJson('/api/devices')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_cannot_see_other_users_sessions(): void
    {
        $userA = $this->makeUser('a@kayfactory.test');
        $userB = $this->makeUser('b@kayfactory.test');
        $this->setupTwoDevices($userA);
        $this->setupTwoDevices($userB);

        Sanctum::actingAs($userB, ['*'], 'sanctum');

        $response = $this->getJson('/api/devices');
        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }

    public function test_revoke_own_session(): void
    {
        $user = $this->makeUser();
        $ctx = $this->setupTwoDevices($user);
        Sanctum::actingAs($user, ['*'], 'sanctum');

        $this->deleteJson("/api/devices/{$ctx['deviceB']->id}")
            ->assertOk()
            ->assertJson(['was_current' => false]);

        $this->assertDatabaseMissing('login_devices', ['id' => $ctx['deviceB']->id]);
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $ctx['tokenBId']]);
    }

    public function test_cannot_revoke_another_users_device(): void
    {
        $userA = $this->makeUser('a@kayfactory.test');
        $userB = $this->makeUser('b@kayfactory.test');
        $ctxA = $this->setupTwoDevices($userA);
        $this->setupTwoDevices($userB);

        Sanctum::actingAs($userB, ['*'], 'sanctum');

        $this->deleteJson("/api/devices/{$ctxA['deviceA']->id}")->assertNotFound();
        $this->assertDatabaseHas('login_devices', ['id' => $ctxA['deviceA']->id]);
    }

    public function test_revoke_others_keeps_current_session(): void
    {
        $user = $this->makeUser();
        $ctx = $this->setupTwoDevices($user);

        // Authenticate with a real Bearer token
        $this->withHeader('Authorization', "Bearer {$ctx['tokenA']}")
            ->postJson('/api/devices/revoke-others')
            ->assertOk()
            ->assertJson(['revoked_count' => 1]);

        $this->assertDatabaseMissing('login_devices', ['id' => $ctx['deviceB']->id]);
        $this->assertDatabaseHas('login_devices', ['id' => $ctx['deviceA']->id]);
    }

    public function test_revoke_all_wipes_everything(): void
    {
        $user = $this->makeUser();
        $ctx = $this->setupTwoDevices($user);

        $this->withHeader('Authorization', "Bearer {$ctx['tokenA']}")
            ->postJson('/api/devices/revoke-all')
            ->assertOk()
            ->assertJson(['was_current' => true]);

        $this->assertDatabaseCount('login_devices', 0);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

     public function test_revoked_token_cannot_authenticate(): void
    {
        $user = $this->makeUser();
        $ctx = $this->setupTwoDevices($user);

        // Revoke device A using its own Bearer token
        $this->withHeader('Authorization', "Bearer {$ctx['tokenA']}")
            ->deleteJson("/api/devices/{$ctx['deviceA']->id}")
            ->assertOk();

        // Sanctum caches the resolved user inside a single test.
        // Forget guards so the next request re-authenticates from scratch.
        $this->app['auth']->forgetGuards();

        // The token should now be rejected
        $this->withHeader('Authorization', "Bearer {$ctx['tokenA']}")
            ->getJson('/api/me')
            ->assertUnauthorized();
    }
}