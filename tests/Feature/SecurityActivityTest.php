<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SecurityActivityTest extends TestCase
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

    protected function logSecurityEvent(User $user, string $action, array $attrs = []): AuditLog
    {
        AuditLog::log($action, $user, ['attributes' => $attrs], $user);

        return AuditLog::where('user_id', $user->id)
            ->where('action', $action)
            ->latest('id')
            ->firstOrFail();
    }

    protected function logNonSecurityEvent(User $user, string $action): void
    {
        AuditLog::log($action, $user, ['attributes' => ['foo' => 'bar']], $user);
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->getJson('/api/me/security/activity')->assertUnauthorized();
    }

    public function test_user_sees_own_security_events(): void
    {
        $user = $this->makeUser();
        $this->logSecurityEvent($user, 'login_success', ['browser' => 'Chrome']);

        Sanctum::actingAs($user, ['*'], 'sanctum');

        $response = $this->getJson('/api/me/security/activity');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.action', 'login_success')
            ->assertJsonPath('data.0.context.browser', 'Chrome');
    }

    public function test_user_cannot_see_another_users_events(): void
    {
        $userA = $this->makeUser('a@kayfactory.test');
        $userB = $this->makeUser('b@kayfactory.test');

        $this->logSecurityEvent($userA, 'login_success');

        Sanctum::actingAs($userB, ['*'], 'sanctum');

        $response = $this->getJson('/api/me/security/activity');

        $response->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_non_whitelisted_events_are_excluded(): void
    {
        $user = $this->makeUser();
        $this->logSecurityEvent($user, 'login_success');
        $this->logNonSecurityEvent($user, 'created');
        $this->logNonSecurityEvent($user, 'updated');
        $this->logNonSecurityEvent($user, 'deleted');

        Sanctum::actingAs($user, ['*'], 'sanctum');

        $response = $this->getJson('/api/me/security/activity');

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_pagination_returns_25_per_page(): void
    {
        $user = $this->makeUser();

        for ($i = 0; $i < 30; $i++) {
            $this->logSecurityEvent($user, 'login_success', ['i' => $i]);
        }

        Sanctum::actingAs($user, ['*'], 'sanctum');

        $page1 = $this->getJson('/api/me/security/activity?page=1');
        $page2 = $this->getJson('/api/me/security/activity?page=2');

        $page1->assertOk()
            ->assertJsonCount(25, 'data')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('meta.total', 30);

        $page2->assertOk()->assertJsonCount(5, 'data');
    }

    public function test_per_page_is_capped_at_100(): void
    {
        $user = $this->makeUser();
        $this->logSecurityEvent($user, 'login_success');

        Sanctum::actingAs($user, ['*'], 'sanctum');

        $response = $this->getJson('/api/me/security/activity?per_page=500');

        $response->assertOk()->assertJsonPath('meta.per_page', 100);
    }

    public function test_events_are_ordered_newest_first(): void
    {
        $user = $this->makeUser();
        $this->logSecurityEvent($user, 'login_success');
        $this->logSecurityEvent($user, 'password_changed');
        $this->logSecurityEvent($user, 'logout');

        Sanctum::actingAs($user, ['*'], 'sanctum');

        $response = $this->getJson('/api/me/security/activity');

        $response->assertOk();
        $actions = collect($response->json('data'))->pluck('action')->all();

        $this->assertSame(['logout', 'password_changed', 'login_success'], $actions);
    }

    public function test_no_sensitive_fields_in_payload(): void
    {
        $user = $this->makeUser();
        $this->logSecurityEvent($user, 'login_success', [
            'browser' => 'Chrome',
            // Even if the caller tries to sneak secrets into changes,
            // AuditLog::filterSensitive() should strip them before write.
            'password' => 'should-never-appear',
            'token' => 'should-never-appear',
            'two_factor_secret' => 'should-never-appear',
        ]);

        Sanctum::actingAs($user, ['*'], 'sanctum');

        $response = $this->getJson('/api/me/security/activity');
        $response->assertOk();

        $raw = json_encode($response->json());
        $this->assertStringNotContainsString('should-never-appear', $raw);
        $this->assertArrayNotHasKey('password', $response->json('data.0.context') ?? []);
        $this->assertArrayNotHasKey('token', $response->json('data.0.context') ?? []);
        $this->assertArrayNotHasKey('two_factor_secret', $response->json('data.0.context') ?? []);
    }

    public function test_response_shape_has_data_and_meta(): void
    {
        $user = $this->makeUser();
        $this->logSecurityEvent($user, 'login_success');

        Sanctum::actingAs($user, ['*'], 'sanctum');

        $response = $this->getJson('/api/me/security/activity');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    ['id', 'action', 'created_at', 'ip_address', 'user_agent', 'context'],
                ],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    }
}