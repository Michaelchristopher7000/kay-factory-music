<?php

namespace Tests\Feature;

use App\Models\Artist;
use App\Models\Role;
use App\Models\Track;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TrackTest extends TestCase
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
        $this->getJson('/api/tracks')->assertStatus(401);
        $this->postJson('/api/tracks', [])->assertStatus(401);
    }

    // ---------------------------------------------------------------
    // View / Index
    // ---------------------------------------------------------------

    public function test_any_authenticated_role_can_list_tracks(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));
        Track::factory()->count(3)->create();

        $this->getJson('/api/tracks')
            ->assertStatus(200)
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_show_returns_single_track(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));
        $track = Track::factory()->create();

        $this->getJson("/api/tracks/{$track->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $track->id);
    }

    // ---------------------------------------------------------------
    // Create authorization
    // ---------------------------------------------------------------

    public function test_super_admin_can_create_track(): void
    {
        Sanctum::actingAs($this->userWithRole('super-admin'));
        $artist = Artist::factory()->create();

        $this->postJson('/api/tracks', [
            'artist_id' => $artist->id,
            'title' => 'SA Track',
        ])->assertStatus(201);
    }

    public function test_label_manager_can_create_track(): void
    {
        Sanctum::actingAs($this->userWithRole('label-manager'));
        $artist = Artist::factory()->create();

        $this->postJson('/api/tracks', [
            'artist_id' => $artist->id,
            'title' => 'LM Track',
        ])->assertStatus(201);
    }

    public function test_ar_can_create_track(): void
    {
        Sanctum::actingAs($this->userWithRole('ar'));
        $artist = Artist::factory()->create();

        $this->postJson('/api/tracks', [
            'artist_id' => $artist->id,
            'title' => 'AR Track',
        ])->assertStatus(201);
    }

    public function test_artist_manager_can_create_track(): void
    {
        Sanctum::actingAs($this->userWithRole('artist-manager'));
        $artist = Artist::factory()->create();

        $this->postJson('/api/tracks', [
            'artist_id' => $artist->id,
            'title' => 'AM Track',
        ])->assertStatus(201);
    }

    public function test_marketing_staff_cannot_create_track(): void
    {
        Sanctum::actingAs($this->userWithRole('marketing-staff'));
        $artist = Artist::factory()->create();

        $this->postJson('/api/tracks', [
            'artist_id' => $artist->id,
            'title' => 'Blocked',
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

        $this->postJson('/api/tracks', [
            'artist_id' => $artist->id,
            'title' => 'Spoof Test',
            'created_by' => $other->id,
        ])->assertStatus(201);

        $track = Track::where('title', 'Spoof Test')->first();
        $this->assertEquals($creator->id, $track->created_by);
        $this->assertNotEquals($other->id, $track->created_by);
    }

    public function test_track_code_is_auto_generated_and_unique(): void
    {
        Sanctum::actingAs($this->userWithRole('label-manager'));
        $artist = Artist::factory()->create();

        $this->postJson('/api/tracks', [
            'artist_id' => $artist->id,
            'title' => 'T1',
        ])->assertStatus(201);

        $this->postJson('/api/tracks', [
            'artist_id' => $artist->id,
            'title' => 'T2',
        ])->assertStatus(201);

        $codes = Track::pluck('track_code')->all();

        $this->assertCount(2, $codes);
        $this->assertCount(2, array_unique($codes));

        foreach ($codes as $code) {
            $this->assertMatchesRegularExpression('/^KFM-TRK-\d{4}$/', $code);
        }
    }

    // ---------------------------------------------------------------
    // Update authorization
    // ---------------------------------------------------------------

    public function test_label_manager_can_update_track(): void
    {
        Sanctum::actingAs($this->userWithRole('label-manager'));
        $track = Track::factory()->create();

        $this->putJson("/api/tracks/{$track->id}", ['title' => 'Updated'])
            ->assertStatus(200)
            ->assertJsonPath('data.title', 'Updated');
    }

    public function test_ar_can_update_track(): void
    {
        Sanctum::actingAs($this->userWithRole('ar'));
        $track = Track::factory()->create();

        $this->patchJson("/api/tracks/{$track->id}", ['genre' => 'Afrobeats'])
            ->assertStatus(200);
    }

    public function test_artist_manager_can_update_track(): void
    {
        Sanctum::actingAs($this->userWithRole('artist-manager'));
        $track = Track::factory()->create();

        $this->patchJson("/api/tracks/{$track->id}", ['bpm' => 120])
            ->assertStatus(200);
    }

    public function test_marketing_staff_cannot_update_track(): void
    {
        Sanctum::actingAs($this->userWithRole('marketing-staff'));
        $track = Track::factory()->create();

        $this->patchJson("/api/tracks/{$track->id}", ['bpm' => 120])
            ->assertStatus(403);
    }

    public function test_super_admin_can_update_any_track(): void
    {
        Sanctum::actingAs($this->userWithRole('super-admin'));
        $track = Track::factory()->create();

        $this->patchJson("/api/tracks/{$track->id}", ['key' => 'Am'])
            ->assertStatus(200);
    }

    // ---------------------------------------------------------------
    // Delete authorization + soft delete
    // ---------------------------------------------------------------

    public function test_label_manager_can_soft_delete_track(): void
    {
        Sanctum::actingAs($this->userWithRole('label-manager'));
        $track = Track::factory()->create();

        $this->deleteJson("/api/tracks/{$track->id}")->assertStatus(200);

        $this->assertSoftDeleted('tracks', ['id' => $track->id]);
    }

    public function test_super_admin_can_soft_delete_track(): void
    {
        Sanctum::actingAs($this->userWithRole('super-admin'));
        $track = Track::factory()->create();

        $this->deleteJson("/api/tracks/{$track->id}")->assertStatus(200);
        $this->assertSoftDeleted('tracks', ['id' => $track->id]);
    }

    public function test_ar_cannot_delete_track(): void
    {
        Sanctum::actingAs($this->userWithRole('ar'));
        $track = Track::factory()->create();

        $this->deleteJson("/api/tracks/{$track->id}")->assertStatus(403);
        $this->assertDatabaseHas('tracks', ['id' => $track->id, 'deleted_at' => null]);
    }

    public function test_artist_manager_cannot_delete_track(): void
    {
        Sanctum::actingAs($this->userWithRole('artist-manager'));
        $track = Track::factory()->create();

        $this->deleteJson("/api/tracks/{$track->id}")->assertStatus(403);
    }

    // ---------------------------------------------------------------
    // Artist relationship + soft-deleted artist behavior
    // ---------------------------------------------------------------

    public function test_track_belongs_to_artist(): void
    {
        $artist = Artist::factory()->create();
        $track = Track::factory()->create(['artist_id' => $artist->id]);

        $this->assertEquals($artist->id, $track->artist->id);
    }

    public function test_track_remains_queryable_when_artist_is_soft_deleted(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));

        $artist = Artist::factory()->create();
        $track = Track::factory()->create(['artist_id' => $artist->id]);

        $artist->delete();

        $this->assertDatabaseHas('tracks', ['id' => $track->id, 'deleted_at' => null]);

        $this->getJson("/api/tracks/{$track->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $track->id);
    }

    // ---------------------------------------------------------------
    // JSON array casts
    // ---------------------------------------------------------------

    public function test_writers_producers_featured_artists_are_returned_as_arrays(): void
    {
        Sanctum::actingAs($this->userWithRole('label-manager'));
        $artist = Artist::factory()->create();

        $response = $this->postJson('/api/tracks', [
            'artist_id' => $artist->id,
            'title' => 'Array Test',
            'writers' => ['Writer A', 'Writer B'],
            'producers' => ['Producer X'],
            'featured_artists' => ['Featured Y'],
        ])->assertStatus(201);

        $response
            ->assertJsonPath('data.writers', ['Writer A', 'Writer B'])
            ->assertJsonPath('data.producers', ['Producer X'])
            ->assertJsonPath('data.featured_artists', ['Featured Y']);
    }

    // ---------------------------------------------------------------
    // Filters
    // ---------------------------------------------------------------

    public function test_search_filter(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));

        Track::factory()->create(['title' => 'Findme Song']);
        Track::factory()->create(['title' => 'Other One']);

        $response = $this->getJson('/api/tracks?search=Findme')->assertStatus(200);

        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Findme Song', $response->json('data.0.title'));
    }

    public function test_artist_id_filter(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));

        $artistA = Artist::factory()->create();
        $artistB = Artist::factory()->create();

        Track::factory()->count(2)->create(['artist_id' => $artistA->id]);
        Track::factory()->create(['artist_id' => $artistB->id]);

        $response = $this->getJson("/api/tracks?artist_id={$artistA->id}")->assertStatus(200);

        $this->assertCount(2, $response->json('data'));
    }

    public function test_genre_filter(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));

        Track::factory()->count(3)->create(['genre' => 'Afrobeats']);
        Track::factory()->create(['genre' => 'R&B']);

        $response = $this->getJson('/api/tracks?genre=Afrobeats')->assertStatus(200);

        $this->assertCount(3, $response->json('data'));
    }

    public function test_is_explicit_filter(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));

        Track::factory()->count(2)->create(['is_explicit' => true]);
        Track::factory()->create(['is_explicit' => false]);

        $response = $this->getJson('/api/tracks?is_explicit=true')->assertStatus(200);

        $this->assertCount(2, $response->json('data'));
    }

    public function test_pagination(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));
        Track::factory()->count(25)->create();

        $response = $this->getJson('/api/tracks?per_page=10')->assertStatus(200);

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

        $this->postJson('/api/tracks', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['artist_id', 'title']);
    }

    public function test_invalid_artist_id_returns_422(): void
    {
        Sanctum::actingAs($this->userWithRole('label-manager'));

        $this->postJson('/api/tracks', [
            'artist_id' => 99999,
            'title' => 'X',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['artist_id']);
    }

    public function test_duplicate_isrc_returns_422(): void
    {
        Sanctum::actingAs($this->userWithRole('label-manager'));
        $artist = Artist::factory()->create();

        $this->postJson('/api/tracks', [
            'artist_id' => $artist->id,
            'title' => 'A',
            'isrc' => 'USRC17607839',
        ])->assertStatus(201);

        $this->postJson('/api/tracks', [
            'artist_id' => $artist->id,
            'title' => 'B',
            'isrc' => 'USRC17607839',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['isrc']);
    }

    public function test_update_isrc_unique_ignores_self(): void
    {
        Sanctum::actingAs($this->userWithRole('label-manager'));
        $track = Track::factory()->create(['isrc' => 'USRC17607839']);

        $this->patchJson("/api/tracks/{$track->id}", [
            'isrc' => 'USRC17607839',
        ])->assertStatus(200);
    }

    public function test_bpm_out_of_range_returns_422(): void
    {
        Sanctum::actingAs($this->userWithRole('label-manager'));
        $artist = Artist::factory()->create();

        $this->postJson('/api/tracks', [
            'artist_id' => $artist->id,
            'title' => 'X',
            'bpm' => 5000,
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['bpm']);
    }
}