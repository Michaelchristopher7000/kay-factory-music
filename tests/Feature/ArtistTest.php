<?php

namespace Tests\Feature;

use App\Models\Artist;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ArtistTest extends TestCase
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
        $this->getJson('/api/artists')->assertStatus(401);
        $this->postJson('/api/artists', [])->assertStatus(401);
    }

    // ---------------------------------------------------------------
    // View / Index
    // ---------------------------------------------------------------

    public function test_any_authenticated_role_can_list_artists(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));
        Artist::factory()->count(3)->create();

        $this->getJson('/api/artists')
            ->assertStatus(200)
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_show_returns_single_artist(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));
        $artist = Artist::factory()->create();

        $this->getJson("/api/artists/{$artist->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $artist->id);
    }

    // ---------------------------------------------------------------
    // Create authorization
    // ---------------------------------------------------------------

    public function test_super_admin_can_create_artist(): void
    {
        Sanctum::actingAs($this->userWithRole('super-admin'));
        $this->postJson('/api/artists', ['name' => 'SA Artist'])
            ->assertStatus(201);
    }

    public function test_label_manager_can_create_artist(): void
    {
        Sanctum::actingAs($this->userWithRole('label-manager'));
        $this->postJson('/api/artists', ['name' => 'LM Artist'])
            ->assertStatus(201);
    }

    public function test_ar_can_create_artist(): void
    {
        Sanctum::actingAs($this->userWithRole('ar'));
        $this->postJson('/api/artists', ['name' => 'AR Artist'])
            ->assertStatus(201);
    }

    public function test_artist_manager_can_create_artist(): void
    {
        Sanctum::actingAs($this->userWithRole('artist-manager'));
        $this->postJson('/api/artists', ['name' => 'AM Artist'])
            ->assertStatus(201);
    }

    public function test_marketing_staff_cannot_create_artist(): void
    {
        Sanctum::actingAs($this->userWithRole('marketing-staff'));
        $this->postJson('/api/artists', ['name' => 'Blocked'])
            ->assertStatus(403);
    }

    public function test_finance_staff_cannot_create_artist(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));
        $this->postJson('/api/artists', ['name' => 'Blocked'])
            ->assertStatus(403);
    }

    // ---------------------------------------------------------------
    // Create side-effects
    // ---------------------------------------------------------------

    public function test_created_by_is_taken_from_authenticated_user(): void
    {
        $creator = $this->userWithRole('label-manager');
        $other = $this->userWithRole('label-manager');
        Sanctum::actingAs($creator);

        $this->postJson('/api/artists', [
            'name' => 'Spoof Test',
            'created_by' => $other->id, // attempt to spoof
        ])->assertStatus(201);

        $artist = Artist::where('name', 'Spoof Test')->first();
        $this->assertEquals($creator->id, $artist->created_by);
        $this->assertNotEquals($other->id, $artist->created_by);
    }

    public function test_artist_code_is_auto_generated_and_unique(): void
    {
        Sanctum::actingAs($this->userWithRole('label-manager'));

        $this->postJson('/api/artists', ['name' => 'A1'])->assertStatus(201);
        $this->postJson('/api/artists', ['name' => 'A2'])->assertStatus(201);

        $codes = Artist::pluck('artist_code')->all();

        $this->assertCount(2, $codes);
        $this->assertCount(2, array_unique($codes));

        foreach ($codes as $code) {
            $this->assertMatchesRegularExpression('/^KFM-ART-\d{4}$/', $code);
        }
    }

    // ---------------------------------------------------------------
    // Update authorization
    // ---------------------------------------------------------------

    public function test_label_manager_can_update_artist(): void
    {
        Sanctum::actingAs($this->userWithRole('label-manager'));
        $artist = Artist::factory()->create();

        $this->putJson("/api/artists/{$artist->id}", ['name' => 'Updated'])
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated');
    }

    public function test_artist_manager_can_update_artist(): void
    {
        Sanctum::actingAs($this->userWithRole('artist-manager'));
        $artist = Artist::factory()->create();

        $this->patchJson("/api/artists/{$artist->id}", ['city' => 'Abuja'])
            ->assertStatus(200);
    }

    public function test_ar_cannot_update_artist(): void
    {
        Sanctum::actingAs($this->userWithRole('ar'));
        $artist = Artist::factory()->create();

        $this->patchJson("/api/artists/{$artist->id}", ['city' => 'Abuja'])
            ->assertStatus(403);
    }

    public function test_super_admin_can_update_any_artist(): void
    {
        Sanctum::actingAs($this->userWithRole('super-admin'));
        $artist = Artist::factory()->create();

        $this->patchJson("/api/artists/{$artist->id}", ['city' => 'Lagos'])
            ->assertStatus(200);
    }

    // ---------------------------------------------------------------
    // Delete authorization + soft delete
    // ---------------------------------------------------------------

    public function test_label_manager_can_soft_delete_artist(): void
    {
        Sanctum::actingAs($this->userWithRole('label-manager'));
        $artist = Artist::factory()->create();

        $this->deleteJson("/api/artists/{$artist->id}")->assertStatus(200);

        $this->assertSoftDeleted('artists', ['id' => $artist->id]);
    }

    public function test_super_admin_can_soft_delete_artist(): void
    {
        Sanctum::actingAs($this->userWithRole('super-admin'));
        $artist = Artist::factory()->create();

        $this->deleteJson("/api/artists/{$artist->id}")->assertStatus(200);
        $this->assertSoftDeleted('artists', ['id' => $artist->id]);
    }

    public function test_artist_manager_cannot_delete_artist(): void
    {
        Sanctum::actingAs($this->userWithRole('artist-manager'));
        $artist = Artist::factory()->create();

        $this->deleteJson("/api/artists/{$artist->id}")->assertStatus(403);
        $this->assertDatabaseHas('artists', ['id' => $artist->id, 'deleted_at' => null]);
    }

    // ---------------------------------------------------------------
    // Filters
    // ---------------------------------------------------------------

    public function test_search_filter(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));

        Artist::factory()->create(['name' => 'Findme Singer']);
        Artist::factory()->create(['name' => 'Other One']);

        $response = $this->getJson('/api/artists?search=Findme')->assertStatus(200);

        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Findme Singer', $response->json('data.0.name'));
    }

    public function test_status_filter(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));

        Artist::factory()->count(2)->create(['status' => 'signed']);
        Artist::factory()->create(['status' => 'in_talks']);

        $response = $this->getJson('/api/artists?status=signed')->assertStatus(200);

        $this->assertCount(2, $response->json('data'));
    }

    public function test_genre_filter(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));

        Artist::factory()->count(3)->create(['genre' => 'Afrobeats']);
        Artist::factory()->create(['genre' => 'R&B']);

        $response = $this->getJson('/api/artists?genre=Afrobeats')->assertStatus(200);

        $this->assertCount(3, $response->json('data'));
    }

    public function test_manager_id_filter(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));

        $manager = $this->userWithRole('artist-manager');
        Artist::factory()->count(2)->create(['manager_id' => $manager->id]);
        Artist::factory()->create(['manager_id' => null]);

        $response = $this->getJson("/api/artists?manager_id={$manager->id}")->assertStatus(200);

        $this->assertCount(2, $response->json('data'));
    }

    public function test_pagination(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));
        Artist::factory()->count(25)->create();

        $response = $this->getJson('/api/artists?per_page=10')->assertStatus(200);

        $this->assertCount(10, $response->json('data'));
        $this->assertEquals(25, $response->json('meta.total'));
        $this->assertEquals(10, $response->json('meta.per_page'));
    }

    // ---------------------------------------------------------------
    // Validation
    // ---------------------------------------------------------------

    public function test_missing_name_returns_422(): void
    {
        Sanctum::actingAs($this->userWithRole('label-manager'));

        $this->postJson('/api/artists', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_invalid_status_returns_422(): void
    {
        Sanctum::actingAs($this->userWithRole('label-manager'));

        $this->postJson('/api/artists', [
            'name' => 'X',
            'status' => 'active',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['status']);
    }

    public function test_invalid_social_link_returns_422(): void
    {
        Sanctum::actingAs($this->userWithRole('label-manager'));

        $this->postJson('/api/artists', [
            'name' => 'X',
            'social_links' => ['spotify' => 'not-a-url'],
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['social_links.spotify']);
    }

    public function test_invalid_manager_id_returns_422(): void
    {
        Sanctum::actingAs($this->userWithRole('label-manager'));

        $this->postJson('/api/artists', [
            'name' => 'X',
            'manager_id' => 99999,
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['manager_id']);
    }
}