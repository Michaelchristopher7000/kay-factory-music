<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
    }

    protected function userWithRole(string $slug): User
    {
        $role = Role::where('slug', $slug)->firstOrFail();
        return User::factory()->create(['role_id' => $role->id]);
    }

    protected function makeNotification(User $user, array $data = [], ?string $readAt = null): DatabaseNotification
    {
        return $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'TestNotification',
            'data' => array_merge([
                'title' => 'Test Title',
                'message' => 'Test Message',
                'entity_type' => 'Artist',
                'entity_id' => 1,
                'entity_code' => 'KFM-ART-0001',
                'action_url' => '/artists/1',
                'context' => [],
            ], $data),
            'read_at' => $readAt,
        ]);
    }

    // =============================================================
    // Access control
    // =============================================================

    public function test_unauthenticated_request_returns_401(): void
    {
        $this->getJson('/api/notifications')->assertStatus(401);
        $this->getJson('/api/notifications/unread-count')->assertStatus(401);
    }

    public function test_user_can_list_own_notifications(): void
    {
        $user = $this->userWithRole('general-staff');
        Sanctum::actingAs($user);

        $this->makeNotification($user);
        $this->makeNotification($user);

        $this->getJson('/api/notifications')
            ->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_user_does_not_see_others_notifications(): void
    {
        $user = $this->userWithRole('general-staff');
        $other = User::factory()->create();

        Sanctum::actingAs($user);
        $this->makeNotification($other);
        $this->makeNotification($user);

        $this->getJson('/api/notifications')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_user_cannot_show_other_users_notification(): void
    {
        $user = $this->userWithRole('general-staff');
        $other = User::factory()->create();
        $foreign = $this->makeNotification($other);

        Sanctum::actingAs($user);

        $this->getJson("/api/notifications/{$foreign->id}")
            ->assertStatus(404);
    }

    public function test_user_cannot_mark_other_users_notification_as_read(): void
    {
        $user = $this->userWithRole('general-staff');
        $other = User::factory()->create();
        $foreign = $this->makeNotification($other);

        Sanctum::actingAs($user);

        $this->patchJson("/api/notifications/{$foreign->id}/read")
            ->assertStatus(404);

        $this->assertNull($foreign->fresh()->read_at);
    }

    public function test_user_cannot_delete_other_users_notification(): void
    {
        $user = $this->userWithRole('general-staff');
        $other = User::factory()->create();
        $foreign = $this->makeNotification($other);

        Sanctum::actingAs($user);

        $this->deleteJson("/api/notifications/{$foreign->id}")->assertStatus(404);
        $this->assertDatabaseHas('notifications', ['id' => $foreign->id]);
    }

    // =============================================================
    // Read / unread
    // =============================================================

    public function test_user_can_mark_own_notification_as_read(): void
    {
        $user = $this->userWithRole('general-staff');
        Sanctum::actingAs($user);
        $n = $this->makeNotification($user);

        $this->patchJson("/api/notifications/{$n->id}/read")
            ->assertStatus(200)
            ->assertJsonPath('data.is_read', true);

        $this->assertNotNull($n->fresh()->read_at);
    }

    public function test_user_can_mark_all_own_notifications_as_read(): void
    {
        $user = $this->userWithRole('general-staff');
        $other = User::factory()->create();

        Sanctum::actingAs($user);

        $this->makeNotification($user);
        $this->makeNotification($user);
        $foreign = $this->makeNotification($other);

        $this->patchJson('/api/notifications/read-all')->assertStatus(200);

        $this->assertEquals(0, $user->fresh()->unreadNotifications()->count());
        $this->assertNull($foreign->fresh()->read_at); // other user untouched
    }

    public function test_unread_count_is_correct(): void
    {
        $user = $this->userWithRole('general-staff');
        Sanctum::actingAs($user);

        $this->makeNotification($user);
        $this->makeNotification($user);
        $this->makeNotification($user, [], now()->toIso8601String());

        $this->getJson('/api/notifications/unread-count')
            ->assertStatus(200)
            ->assertJsonPath('data.unread_count', 2);
    }

    public function test_unread_filter(): void
    {
        $user = $this->userWithRole('general-staff');
        Sanctum::actingAs($user);

        $this->makeNotification($user);
        $this->makeNotification($user, [], now()->toIso8601String());

        $this->getJson('/api/notifications?unread=true')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    // =============================================================
    // Delete
    // =============================================================

    public function test_user_can_delete_own_notification(): void
    {
        $user = $this->userWithRole('general-staff');
        Sanctum::actingAs($user);
        $n = $this->makeNotification($user);

        $this->deleteJson("/api/notifications/{$n->id}")->assertStatus(200);
        $this->assertDatabaseMissing('notifications', ['id' => $n->id]);
    }

    // =============================================================
    // Pagination + ordering
    // =============================================================

    public function test_pagination_defaults_to_25(): void
    {
        $user = $this->userWithRole('general-staff');
        Sanctum::actingAs($user);

        for ($i = 0; $i < 30; $i++) {
            $this->makeNotification($user);
        }

        $response = $this->getJson('/api/notifications')->assertStatus(200);
        $this->assertCount(25, $response->json('data'));
        $this->assertEquals(25, $response->json('meta.per_page'));
    }

    public function test_pagination_caps_at_100(): void
    {
        $user = $this->userWithRole('general-staff');
        Sanctum::actingAs($user);

        for ($i = 0; $i < 120; $i++) {
            $this->makeNotification($user);
        }

        $response = $this->getJson('/api/notifications?per_page=500')->assertStatus(200);
        $this->assertCount(100, $response->json('data'));
        $this->assertEquals(100, $response->json('meta.per_page'));
    }

    public function test_notifications_ordered_newest_first(): void
    {
        $user = $this->userWithRole('general-staff');
        Sanctum::actingAs($user);

        $old = $this->makeNotification($user, ['title' => 'Old']);
        // Ensure timestamp difference
        \Illuminate\Support\Carbon::setTestNow(now()->addMinute());
        $new = $this->makeNotification($user, ['title' => 'New']);
        \Illuminate\Support\Carbon::setTestNow();

        $response = $this->getJson('/api/notifications')->assertStatus(200);

        $this->assertEquals($new->id, $response->json('data.0.id'));
        $this->assertEquals($old->id, $response->json('data.1.id'));
    }

    // =============================================================
    // Security — payload
    // =============================================================

    public function test_response_payload_has_no_sensitive_fields(): void
    {
        $user = $this->userWithRole('general-staff');
        Sanctum::actingAs($user);

        $this->makeNotification($user);

        $response = $this->getJson('/api/notifications')->assertStatus(200);
        $json = json_encode($response->json());

        $this->assertStringNotContainsString('password', $json);
        $this->assertStringNotContainsString('remember_token', $json);
        $this->assertStringNotContainsString('MAIL_PASSWORD', $json);
        $this->assertStringNotContainsString('MAIL_USERNAME', $json);
    }
}