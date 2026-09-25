<?php

namespace Tests\Feature;

use App\Models\Artist;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuditLogTest extends TestCase
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

    // ===============================================================
    // 1. Created event
    // ===============================================================

    public function test_creating_tracked_model_writes_created_log(): void
    {
        $artist = Artist::factory()->create(['name' => 'New Artist']);

        $log = AuditLog::where('action', 'created')
            ->where('model_type', 'Artist')
            ->where('model_id', $artist->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertStringContainsString('New Artist', $log->model_label);
        $this->assertArrayHasKey('attributes', $log->changes);
        $this->assertEquals('New Artist', $log->changes['attributes']['name']);
    }

    // ===============================================================
    // 2. Updated event
    // ===============================================================

    public function test_updating_tracked_model_writes_update_log(): void
    {
        $artist = Artist::factory()->create(['name' => 'Before']);
        $artist->update(['name' => 'After']);

        $log = AuditLog::where('action', 'updated')
            ->where('model_type', 'Artist')
            ->where('model_id', $artist->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('Before', $log->changes['before']['name']);
        $this->assertEquals('After', $log->changes['after']['name']);
    }

    // ===============================================================
    // 3. Deleted event
    // ===============================================================

    public function test_deleting_tracked_model_writes_deleted_log(): void
    {
        $artist = Artist::factory()->create();
        $artist->delete();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'deleted',
            'model_type' => 'Artist',
            'model_id' => $artist->id,
        ]);
    }

    // ===============================================================
    // 4. Restored event
    // ===============================================================

    public function test_restoring_tracked_model_writes_restored_log(): void
    {
        $artist = Artist::factory()->create();
        $artist->delete();
        $artist->restore();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'restored',
            'model_type' => 'Artist',
            'model_id' => $artist->id,
        ]);
    }

    // ===============================================================
    // 5. Before/after contains only changed values
    // ===============================================================

    public function test_only_changed_fields_appear_in_before_and_after(): void
    {
        $artist = Artist::factory()->create([
            'name' => 'Original',
            'genre' => 'Afrobeats',
            'city' => 'Lagos',
        ]);

        $artist->update(['name' => 'Changed']);

        $log = AuditLog::where('action', 'updated')
            ->where('model_id', $artist->id)
            ->latest('id')
            ->first();

        $this->assertArrayHasKey('name', $log->changes['before']);
        $this->assertArrayHasKey('name', $log->changes['after']);
        $this->assertArrayNotHasKey('genre', $log->changes['before']);
        $this->assertArrayNotHasKey('city', $log->changes['after']);
    }

    // ===============================================================
    // 6. Sensitive / internal fields not recorded
    // ===============================================================

    public function test_internal_timestamps_not_recorded_in_changes(): void
    {
        $artist = Artist::factory()->create();
        $artist->update(['name' => 'X']);

        $log = AuditLog::where('action', 'updated')
            ->where('model_id', $artist->id)
            ->latest('id')
            ->first();

        $this->assertArrayNotHasKey('updated_at', $log->changes['before'] ?? []);
        $this->assertArrayNotHasKey('updated_at', $log->changes['after'] ?? []);
        $this->assertArrayNotHasKey('created_at', $log->changes['before'] ?? []);
        $this->assertArrayNotHasKey('deleted_at', $log->changes['after'] ?? []);
    }

    // ===============================================================
    // 7. User snapshot
    // ===============================================================

    public function test_user_snapshot_recorded_for_authenticated_actions(): void
    {
        $user = $this->userWithRole('label-manager');
        Sanctum::actingAs($user);

        $artist = Artist::factory()->create();

        $log = AuditLog::where('model_id', $artist->id)
            ->where('action', 'created')
            ->first();

        $this->assertEquals($user->id, $log->user_id);
        $this->assertEquals($user->email, $log->user_email);
        $this->assertEquals($user->name, $log->user_name);
    }

    // ===============================================================
    // 8. System / CLI actions
    // ===============================================================

    public function test_system_actions_recorded_without_authenticated_user(): void
    {
        // No Sanctum::actingAs here — simulates CLI/seeder context.
        $artist = Artist::factory()->create();

        $log = AuditLog::where('model_id', $artist->id)
            ->where('action', 'created')
            ->first();

        $this->assertNull($log->user_id);
        $this->assertEquals('system', $log->user_name);
        $this->assertEquals('system', $log->user_email);
    }

    // ===============================================================
    // 9. SA can view
    // ===============================================================

    public function test_super_admin_can_list_audit_logs(): void
    {
        Sanctum::actingAs($this->userWithRole('super-admin'));
        AuditLog::factory()->count(3)->create();

        $this->getJson('/api/audit-logs')
            ->assertStatus(200)
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    // ===============================================================
    // 10. Label Manager can view
    // ===============================================================

    public function test_label_manager_can_list_audit_logs(): void
    {
        Sanctum::actingAs($this->userWithRole('label-manager'));
        AuditLog::factory()->count(3)->create();

        $this->getJson('/api/audit-logs')->assertStatus(200);
    }

    // ===============================================================
    // 11. Other roles cannot view
    // ===============================================================

    public function test_other_roles_cannot_list_audit_logs(): void
    {
        foreach (['ar', 'artist-manager', 'finance-staff', 'marketing-staff', 'distribution-manager', 'general-staff'] as $slug) {
            Sanctum::actingAs($this->userWithRole($slug));
            $this->getJson('/api/audit-logs')->assertStatus(403);
        }
    }

    // ===============================================================
    // 12. Filters
    // ===============================================================

    public function test_filter_by_action(): void
    {
        Sanctum::actingAs($this->userWithRole('label-manager'));
        AuditLog::factory()->count(2)->create(['action' => 'created']);
        AuditLog::factory()->create(['action' => 'deleted']);

        $response = $this->getJson('/api/audit-logs?action=created')->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_filter_by_model_type_case_insensitive(): void
    {
        Sanctum::actingAs($this->userWithRole('label-manager'));
        AuditLog::factory()->count(2)->create(['model_type' => 'Artist']);
        AuditLog::factory()->create(['model_type' => 'Contract']);

        $response = $this->getJson('/api/audit-logs?model_type=artist')->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_filter_by_model_id(): void
    {
        Sanctum::actingAs($this->userWithRole('label-manager'));
        AuditLog::factory()->count(2)->create(['model_id' => 42]);
        AuditLog::factory()->create(['model_id' => 99]);

        $response = $this->getJson('/api/audit-logs?model_id=42')->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_filter_by_user_id(): void
    {
        Sanctum::actingAs($this->userWithRole('label-manager'));

        $u1 = User::factory()->create();
        $u2 = User::factory()->create();

        AuditLog::factory()->count(2)->create(['user_id' => $u1->id]);
        AuditLog::factory()->create(['user_id' => $u2->id]);

        $response = $this->getJson("/api/audit-logs?user_id={$u1->id}")->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_search_filter_matches_model_label(): void
    {
        Sanctum::actingAs($this->userWithRole('label-manager'));
        AuditLog::factory()->create(['model_label' => 'KFM-ART-9999 - Findme']);
        AuditLog::factory()->create(['model_label' => 'Other']);

        $response = $this->getJson('/api/audit-logs?search=Findme')->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_date_range_filter(): void
    {
        Sanctum::actingAs($this->userWithRole('label-manager'));

        AuditLog::factory()->create(['created_at' => '2026-03-15 10:00:00']);
        AuditLog::factory()->create(['created_at' => '2026-06-15 10:00:00']);
        AuditLog::factory()->create(['created_at' => '2026-09-15 10:00:00']);

        $response = $this->getJson('/api/audit-logs?from=2026-04-01&to=2026-08-31')
            ->assertStatus(200);

        $this->assertCount(1, $response->json('data'));
    }

    // ===============================================================
    // 13. Pagination + max cap
    // ===============================================================

    public function test_pagination_defaults_to_25(): void
    {
        Sanctum::actingAs($this->userWithRole('label-manager'));
        AuditLog::factory()->count(30)->create();

        $response = $this->getJson('/api/audit-logs')->assertStatus(200);

        $this->assertCount(25, $response->json('data'));
        $this->assertEquals(25, $response->json('meta.per_page'));
        $this->assertEquals(30, $response->json('meta.total'));
    }

    public function test_pagination_caps_at_100(): void
    {
        Sanctum::actingAs($this->userWithRole('label-manager'));
        AuditLog::factory()->count(150)->create();

        $response = $this->getJson('/api/audit-logs?per_page=500')->assertStatus(200);

        $this->assertCount(100, $response->json('data'));
        $this->assertEquals(100, $response->json('meta.per_page'));
    }

    // ===============================================================
    // 14. Ordering
    // ===============================================================

    public function test_ordering_is_newest_first(): void
    {
        Sanctum::actingAs($this->userWithRole('label-manager'));

        $old = AuditLog::factory()->create(['created_at' => '2026-01-01 10:00:00']);
        $new = AuditLog::factory()->create(['created_at' => '2026-06-01 10:00:00']);

        $response = $this->getJson('/api/audit-logs')->assertStatus(200);

        $this->assertEquals($new->id, $response->json('data.0.id'));
        $this->assertEquals($old->id, $response->json('data.1.id'));
    }

    // ===============================================================
    // 15. Show
    // ===============================================================

    public function test_show_returns_single_log(): void
    {
        Sanctum::actingAs($this->userWithRole('label-manager'));
        $log = AuditLog::factory()->create();

        $this->getJson("/api/audit-logs/{$log->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $log->id);
    }

    // ===============================================================
    // 16. Cannot be modified through the API
    // ===============================================================

    public function test_audit_logs_cannot_be_modified_via_api(): void
    {
        Sanctum::actingAs($this->userWithRole('super-admin'));
        $log = AuditLog::factory()->create();

        $this->postJson('/api/audit-logs', [])->assertStatus(405);
        $this->putJson("/api/audit-logs/{$log->id}", [])->assertStatus(405);
        $this->patchJson("/api/audit-logs/{$log->id}", [])->assertStatus(405);
        $this->deleteJson("/api/audit-logs/{$log->id}")->assertStatus(405);
    }
}