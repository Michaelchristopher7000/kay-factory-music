<?php

namespace Tests\Feature;

use App\Models\Artist;
use App\Models\Release;
use App\Models\Role;
use App\Models\Track;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReleaseTest extends TestCase
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

    // ---------------------------------------------------------------
    // Authentication
    // ---------------------------------------------------------------

    public function test_unauthenticated_request_returns_401(): void
    {
        $this->getJson('/api/releases')->assertStatus(401);
        $this->postJson('/api/releases', [])->assertStatus(401);
    }

    // ---------------------------------------------------------------
    // View
    // ---------------------------------------------------------------

    public function test_any_authenticated_role_can_list_releases(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));
        Release::factory()->count(3)->create();

        $this->getJson('/api/releases')
            ->assertStatus(200)
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_show_returns_single_release(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));
        $release = Release::factory()->create();

        $this->getJson("/api/releases/{$release->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $release->id);
    }

    // ---------------------------------------------------------------
    // Create authorization
    // ---------------------------------------------------------------

    public function test_super_admin_can_create_release(): void
    {
        Sanctum::actingAs($this->userWithRole('super-admin'));
        $artist = Artist::factory()->create();

        $this->postJson('/api/releases', [
            'artist_id' => $artist->id,
            'title' => 'SA Release',
            'type' => Release::TYPE_SINGLE,
        ])->assertStatus(201);
    }

    public function test_label_manager_can_create_release(): void
    {
        Sanctum::actingAs($this->userWithRole('label-manager'));
        $artist = Artist::factory()->create();

        $this->postJson('/api/releases', [
            'artist_id' => $artist->id,
            'title' => 'LM Release',
            'type' => Release::TYPE_EP,
        ])->assertStatus(201);
    }

    public function test_ar_can_create_release(): void
    {
        Sanctum::actingAs($this->userWithRole('ar'));
        $artist = Artist::factory()->create();

        $this->postJson('/api/releases', [
            'artist_id' => $artist->id,
            'title' => 'AR Release',
            'type' => Release::TYPE_ALBUM,
        ])->assertStatus(201);
    }

    public function test_artist_manager_can_create_release(): void
    {
        Sanctum::actingAs($this->userWithRole('artist-manager'));
        $artist = Artist::factory()->create();

        $this->postJson('/api/releases', [
            'artist_id' => $artist->id,
            'title' => 'AM Release',
            'type' => Release::TYPE_SINGLE,
        ])->assertStatus(201);
    }

    public function test_distribution_manager_can_create_release(): void
    {
        Sanctum::actingAs($this->userWithRole('distribution-manager'));
        $artist = Artist::factory()->create();

        $this->postJson('/api/releases', [
            'artist_id' => $artist->id,
            'title' => 'DM Release',
            'type' => Release::TYPE_SINGLE,
        ])->assertStatus(201);
    }

    public function test_marketing_staff_cannot_create_release(): void
    {
        Sanctum::actingAs($this->userWithRole('marketing-staff'));
        $artist = Artist::factory()->create();

        $this->postJson('/api/releases', [
            'artist_id' => $artist->id,
            'title' => 'Blocked',
            'type' => Release::TYPE_SINGLE,
        ])->assertStatus(403);
    }

    // ---------------------------------------------------------------
    // Create side-effects
    // ---------------------------------------------------------------

    public function test_created_by_is_taken_from_authenticated_user(): void
    {
        $creator = $this->userWithRole('label-manager');
        $other = $this->userWithRole('label-manager');
        Sanctum::actingAs($creator);
        $artist = Artist::factory()->create();

        $this->postJson('/api/releases', [
            'artist_id' => $artist->id,
            'title' => 'Spoof Test',
            'type' => Release::TYPE_SINGLE,
            'created_by' => $other->id,
        ])->assertStatus(201);

        $release = Release::where('title', 'Spoof Test')->first();
        $this->assertEquals($creator->id, $release->created_by);
        $this->assertNotEquals($other->id, $release->created_by);
    }

    public function test_release_code_is_auto_generated_and_unique(): void
    {
        Sanctum::actingAs($this->userWithRole('label-manager'));
        $artist = Artist::factory()->create();

        $this->postJson('/api/releases', [
            'artist_id' => $artist->id,
            'title' => 'R1',
            'type' => Release::TYPE_SINGLE,
        ])->assertStatus(201);

        $this->postJson('/api/releases', [
            'artist_id' => $artist->id,
            'title' => 'R2',
            'type' => Release::TYPE_SINGLE,
        ])->assertStatus(201);

        $codes = Release::pluck('release_code')->all();

        $this->assertCount(2, $codes);
        $this->assertCount(2, array_unique($codes));

        foreach ($codes as $code) {
            $this->assertMatchesRegularExpression('/^KFM-REL-\d{4}$/', $code);
        }
    }

    // ---------------------------------------------------------------
    // Track attachment
    // ---------------------------------------------------------------

    public function test_create_attaches_tracks_with_position(): void
    {
        Sanctum::actingAs($this->userWithRole('label-manager'));
        $artist = Artist::factory()->create();

        $t1 = Track::factory()->create(['artist_id' => $artist->id, 'title' => 'T1']);
        $t2 = Track::factory()->create(['artist_id' => $artist->id, 'title' => 'T2']);
        $t3 = Track::factory()->create(['artist_id' => $artist->id, 'title' => 'T3']);

        $response = $this->postJson('/api/releases', [
            'artist_id' => $artist->id,
            'title' => 'EP With Tracks',
            'type' => Release::TYPE_EP,
            'track_ids' => [$t2->id, $t1->id, $t3->id],
        ])->assertStatus(201);

        $data = $response->json('data');
        $this->assertCount(3, $data['tracks']);
        $this->assertEquals($t2->id, $data['tracks'][0]['id']);
        $this->assertEquals(1, $data['tracks'][0]['position']);
        $this->assertEquals($t1->id, $data['tracks'][1]['id']);
        $this->assertEquals(2, $data['tracks'][1]['position']);
        $this->assertEquals($t3->id, $data['tracks'][2]['id']);
        $this->assertEquals(3, $data['tracks'][2]['position']);
    }

    public function test_update_syncs_tracks(): void
    {
        Sanctum::actingAs($this->userWithRole('label-manager'));
        $artist = Artist::factory()->create();
        $release = Release::factory()->create(['artist_id' => $artist->id]);

        $t1 = Track::factory()->create(['artist_id' => $artist->id]);
        $t2 = Track::factory()->create(['artist_id' => $artist->id]);

        $this->patchJson("/api/releases/{$release->id}", [
            'track_ids' => [$t1->id],
        ])->assertStatus(200);

        $this->assertEquals(1, $release->fresh()->tracks()->count());

        $this->patchJson("/api/releases/{$release->id}", [
            'track_ids' => [$t2->id],
        ])->assertStatus(200);

        $release->refresh();
        $this->assertEquals(1, $release->tracks()->count());
        $this->assertEquals($t2->id, $release->tracks()->first()->id);
    }

    public function test_track_from_another_artist_is_rejected(): void
    {
        Sanctum::actingAs($this->userWithRole('label-manager'));
        $artistA = Artist::factory()->create();
        $artistB = Artist::factory()->create();
        $foreignTrack = Track::factory()->create(['artist_id' => $artistB->id]);

        $this->postJson('/api/releases', [
            'artist_id' => $artistA->id,
            'title' => 'Bad Mix',
            'type' => Release::TYPE_EP,
            'track_ids' => [$foreignTrack->id],
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['track_ids.0']);
    }

    public function test_changing_artist_without_track_ids_is_rejected(): void
    {
        Sanctum::actingAs($this->userWithRole('label-manager'));
        $artistA = Artist::factory()->create();
        $artistB = Artist::factory()->create();
        $release = Release::factory()->create(['artist_id' => $artistA->id]);

        $this->patchJson("/api/releases/{$release->id}", [
            'artist_id' => $artistB->id,
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['track_ids']);
    }

    // ---------------------------------------------------------------
    // Update authorization
    // ---------------------------------------------------------------

    public function test_label_manager_can_update_release(): void
    {
        Sanctum::actingAs($this->userWithRole('label-manager'));
        $release = Release::factory()->create();

        $this->putJson("/api/releases/{$release->id}", ['title' => 'Updated'])
            ->assertStatus(200)
            ->assertJsonPath('data.title', 'Updated');
    }

    public function test_ar_can_update_release(): void
    {
        Sanctum::actingAs($this->userWithRole('ar'));
        $release = Release::factory()->create();

        $this->patchJson("/api/releases/{$release->id}", ['status' => Release::STATUS_SCHEDULED])
            ->assertStatus(200);
    }

    public function test_artist_manager_can_update_release(): void
    {
        Sanctum::actingAs($this->userWithRole('artist-manager'));
        $release = Release::factory()->create();

        $this->patchJson("/api/releases/{$release->id}", ['label_copy' => '© 2026 Kay Factory'])
            ->assertStatus(200);
    }

    public function test_distribution_manager_can_update_release(): void
    {
        Sanctum::actingAs($this->userWithRole('distribution-manager'));
        $release = Release::factory()->create();

        $this->patchJson("/api/releases/{$release->id}", ['status' => Release::STATUS_ARCHIVED])
            ->assertStatus(200);
    }

    public function test_marketing_staff_cannot_update_release(): void
    {
        Sanctum::actingAs($this->userWithRole('marketing-staff'));
        $release = Release::factory()->create();

        $this->patchJson("/api/releases/{$release->id}", ['title' => 'Nope'])
            ->assertStatus(403);
    }

    // ---------------------------------------------------------------
    // Delete authorization + soft delete
    // ---------------------------------------------------------------

    public function test_label_manager_can_soft_delete_release(): void
    {
        Sanctum::actingAs($this->userWithRole('label-manager'));
        $release = Release::factory()->create();

        $this->deleteJson("/api/releases/{$release->id}")->assertStatus(200);

        $this->assertSoftDeleted('releases', ['id' => $release->id]);
    }

    public function test_ar_cannot_delete_release(): void
    {
        Sanctum::actingAs($this->userWithRole('ar'));
        $release = Release::factory()->create();

        $this->deleteJson("/api/releases/{$release->id}")->assertStatus(403);
    }

    public function test_distribution_manager_cannot_delete_release(): void
    {
        Sanctum::actingAs($this->userWithRole('distribution-manager'));
        $release = Release::factory()->create();

        $this->deleteJson("/api/releases/{$release->id}")->assertStatus(403);
    }

    // ---------------------------------------------------------------
    // Artist relationship + soft-deleted artist behavior
    // ---------------------------------------------------------------

    public function test_release_belongs_to_artist(): void
    {
        $artist = Artist::factory()->create();
        $release = Release::factory()->create(['artist_id' => $artist->id]);

        $this->assertEquals($artist->id, $release->artist->id);
    }

    public function test_release_remains_queryable_when_artist_is_soft_deleted(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));

        $artist = Artist::factory()->create();
        $release = Release::factory()->create(['artist_id' => $artist->id]);

        $artist->delete();

        $this->assertDatabaseHas('releases', ['id' => $release->id, 'deleted_at' => null]);

        $this->getJson("/api/releases/{$release->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $release->id);
    }

    // ---------------------------------------------------------------
    // Filters
    // ---------------------------------------------------------------

    public function test_search_filter(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));

        Release::factory()->create(['title' => 'Findme EP']);
        Release::factory()->create(['title' => 'Other One']);

        $response = $this->getJson('/api/releases?search=Findme')->assertStatus(200);

        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Findme EP', $response->json('data.0.title'));
    }

    public function test_artist_id_filter(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));

        $artistA = Artist::factory()->create();
        $artistB = Artist::factory()->create();

        Release::factory()->count(2)->create(['artist_id' => $artistA->id]);
        Release::factory()->create(['artist_id' => $artistB->id]);

        $response = $this->getJson("/api/releases?artist_id={$artistA->id}")->assertStatus(200);

        $this->assertCount(2, $response->json('data'));
    }

    public function test_type_filter(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));

        Release::factory()->count(3)->create(['type' => Release::TYPE_EP]);
        Release::factory()->create(['type' => Release::TYPE_ALBUM]);

        $response = $this->getJson('/api/releases?type=ep')->assertStatus(200);

        $this->assertCount(3, $response->json('data'));
    }

    public function test_status_filter(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));

        Release::factory()->count(2)->create(['status' => Release::STATUS_RELEASED]);
        Release::factory()->create(['status' => Release::STATUS_DRAFT]);

        $response = $this->getJson('/api/releases?status=released')->assertStatus(200);

        $this->assertCount(2, $response->json('data'));
    }

    public function test_year_filter(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));

        Release::factory()->count(2)->create(['release_date' => '2025-06-01']);
        Release::factory()->create(['release_date' => '2024-06-01']);

        $response = $this->getJson('/api/releases?year=2025')->assertStatus(200);

        $this->assertCount(2, $response->json('data'));
    }

    public function test_pagination(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));
        Release::factory()->count(25)->create();

        $response = $this->getJson('/api/releases?per_page=10')->assertStatus(200);

        $this->assertCount(10, $response->json('data'));
        $this->assertEquals(25, $response->json('meta.total'));
        $this->assertEquals(10, $response->json('meta.per_page'));
    }

    // ---------------------------------------------------------------
    // Validation
    // ---------------------------------------------------------------

    public function test_missing_required_fields_returns_422(): void
    {
        Sanctum::actingAs($this->userWithRole('label-manager'));

        $this->postJson('/api/releases', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['artist_id', 'title', 'type']);
    }

    public function test_invalid_type_returns_422(): void
    {
        Sanctum::actingAs($this->userWithRole('label-manager'));
        $artist = Artist::factory()->create();

        $this->postJson('/api/releases', [
            'artist_id' => $artist->id,
            'title' => 'X',
            'type' => 'bogus',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['type']);
    }

    public function test_invalid_status_returns_422(): void
    {
        Sanctum::actingAs($this->userWithRole('label-manager'));
        $artist = Artist::factory()->create();

        $this->postJson('/api/releases', [
            'artist_id' => $artist->id,
            'title' => 'X',
            'type' => Release::TYPE_SINGLE,
            'status' => 'bogus',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['status']);
    }

    public function test_invalid_artist_id_returns_422(): void
    {
        Sanctum::actingAs($this->userWithRole('label-manager'));

        $this->postJson('/api/releases', [
            'artist_id' => 99999,
            'title' => 'X',
            'type' => Release::TYPE_SINGLE,
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['artist_id']);
    }

    public function test_duplicate_upc_returns_422(): void
    {
        Sanctum::actingAs($this->userWithRole('label-manager'));
        $artist = Artist::factory()->create();

        $this->postJson('/api/releases', [
            'artist_id' => $artist->id,
            'title' => 'A',
            'type' => Release::TYPE_SINGLE,
            'upc' => '012345678901',
        ])->assertStatus(201);

        $this->postJson('/api/releases', [
            'artist_id' => $artist->id,
            'title' => 'B',
            'type' => Release::TYPE_SINGLE,
            'upc' => '012345678901',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['upc']);
    }

    public function test_update_upc_unique_ignores_self(): void
    {
        Sanctum::actingAs($this->userWithRole('label-manager'));
        $release = Release::factory()->create(['upc' => '012345678901']);

        $this->patchJson("/api/releases/{$release->id}", [
            'upc' => '012345678901',
        ])->assertStatus(200);
    }
}