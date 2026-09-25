<?php

namespace Tests\Feature;

use App\Models\Artist;
use App\Models\Distribution;
use App\Models\Release;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DistributionTest extends TestCase
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
        $this->getJson('/api/distributions')->assertStatus(401);
        $this->postJson('/api/distributions', [])->assertStatus(401);
    }

    // ---------------------------------------------------------------
    // View
    // ---------------------------------------------------------------

    public function test_any_authenticated_role_can_list_distributions(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));
        Distribution::factory()->count(3)->create();

        $this->getJson('/api/distributions')
            ->assertStatus(200)
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_show_returns_single_distribution(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));
        $distribution = Distribution::factory()->create();

        $this->getJson("/api/distributions/{$distribution->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $distribution->id);
    }

    // ---------------------------------------------------------------
    // Create authorization
    // ---------------------------------------------------------------

    public function test_super_admin_can_create_distribution(): void
    {
        Sanctum::actingAs($this->userWithRole('super-admin'));
        $release = Release::factory()->create();

        $this->postJson('/api/distributions', [
            'release_id' => $release->id,
            'platform' => 'spotify',
        ])->assertStatus(201);
    }

    public function test_label_manager_can_create_distribution(): void
    {
        Sanctum::actingAs($this->userWithRole('label-manager'));
        $release = Release::factory()->create();

        $this->postJson('/api/distributions', [
            'release_id' => $release->id,
            'platform' => 'apple_music',
        ])->assertStatus(201);
    }

    public function test_distribution_manager_can_create_distribution(): void
    {
        Sanctum::actingAs($this->userWithRole('distribution-manager'));
        $release = Release::factory()->create();

        $this->postJson('/api/distributions', [
            'release_id' => $release->id,
            'platform' => 'boomplay',
        ])->assertStatus(201);
    }

    public function test_ar_cannot_create_distribution(): void
    {
        Sanctum::actingAs($this->userWithRole('ar'));
        $release = Release::factory()->create();

        $this->postJson('/api/distributions', [
            'release_id' => $release->id,
            'platform' => 'spotify',
        ])->assertStatus(403);
    }

    public function test_artist_manager_cannot_create_distribution(): void
    {
        Sanctum::actingAs($this->userWithRole('artist-manager'));
        $release = Release::factory()->create();

        $this->postJson('/api/distributions', [
            'release_id' => $release->id,
            'platform' => 'spotify',
        ])->assertStatus(403);
    }

    public function test_marketing_staff_cannot_create_distribution(): void
    {
        Sanctum::actingAs($this->userWithRole('marketing-staff'));
        $release = Release::factory()->create();

        $this->postJson('/api/distributions', [
            'release_id' => $release->id,
            'platform' => 'spotify',
        ])->assertStatus(403);
    }

    // ---------------------------------------------------------------
    // Create side-effects
    // ---------------------------------------------------------------

    public function test_created_by_is_taken_from_authenticated_user(): void
    {
        $creator = $this->userWithRole('distribution-manager');
        $other = $this->userWithRole('distribution-manager');
        Sanctum::actingAs($creator);
        $release = Release::factory()->create();

        $this->postJson('/api/distributions', [
            'release_id' => $release->id,
            'platform' => 'spotify',
            'created_by' => $other->id,
        ])->assertStatus(201);

        $distribution = Distribution::where('release_id', $release->id)->first();
        $this->assertEquals($creator->id, $distribution->created_by);
        $this->assertNotEquals($other->id, $distribution->created_by);
    }

    public function test_distribution_code_is_auto_generated_and_unique(): void
    {
        Sanctum::actingAs($this->userWithRole('distribution-manager'));
        $release = Release::factory()->create();

        $this->postJson('/api/distributions', [
            'release_id' => $release->id,
            'platform' => 'spotify',
        ])->assertStatus(201);

        $this->postJson('/api/distributions', [
            'release_id' => $release->id,
            'platform' => 'apple_music',
        ])->assertStatus(201);

        $codes = Distribution::pluck('distribution_code')->all();

        $this->assertCount(2, $codes);
        $this->assertCount(2, array_unique($codes));

        foreach ($codes as $code) {
            $this->assertMatchesRegularExpression('/^KFM-DST-\d{4}$/', $code);
        }
    }

    // ---------------------------------------------------------------
    // Unique constraint (release_id, platform)
    // ---------------------------------------------------------------

    public function test_duplicate_release_platform_on_create_returns_422(): void
    {
        Sanctum::actingAs($this->userWithRole('distribution-manager'));
        $release = Release::factory()->create();

        $this->postJson('/api/distributions', [
            'release_id' => $release->id,
            'platform' => 'spotify',
        ])->assertStatus(201);

        $this->postJson('/api/distributions', [
            'release_id' => $release->id,
            'platform' => 'spotify',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['release_id']);
    }

    public function test_same_platform_different_release_is_allowed(): void
    {
        Sanctum::actingAs($this->userWithRole('distribution-manager'));
        $releaseA = Release::factory()->create();
        $releaseB = Release::factory()->create();

        $this->postJson('/api/distributions', [
            'release_id' => $releaseA->id,
            'platform' => 'spotify',
        ])->assertStatus(201);

        $this->postJson('/api/distributions', [
            'release_id' => $releaseB->id,
            'platform' => 'spotify',
        ])->assertStatus(201);
    }

    public function test_update_keeping_same_release_and_platform_is_allowed(): void
    {
        Sanctum::actingAs($this->userWithRole('distribution-manager'));
        $distribution = Distribution::factory()->create([
            'platform' => 'spotify',
        ]);

        $this->patchJson("/api/distributions/{$distribution->id}", [
            'status' => 'live',
        ])->assertStatus(200);
    }

    public function test_update_colliding_release_platform_is_rejected(): void
    {
        Sanctum::actingAs($this->userWithRole('distribution-manager'));

        $release = Release::factory()->create();

        Distribution::factory()->create([
            'release_id' => $release->id,
            'platform' => 'spotify',
        ]);

        $other = Distribution::factory()->create([
            'release_id' => $release->id,
            'platform' => 'apple_music',
        ]);

        $this->patchJson("/api/distributions/{$other->id}", [
            'platform' => 'spotify',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['release_id']);
    }

    // ---------------------------------------------------------------
    // Update authorization
    // ---------------------------------------------------------------

    public function test_label_manager_can_update_distribution(): void
    {
        Sanctum::actingAs($this->userWithRole('label-manager'));
        $distribution = Distribution::factory()->create();

        $this->putJson("/api/distributions/{$distribution->id}", [
            'status' => 'submitted',
        ])->assertStatus(200)
          ->assertJsonPath('data.status', 'submitted');
    }

    public function test_distribution_manager_can_update_distribution(): void
    {
        Sanctum::actingAs($this->userWithRole('distribution-manager'));
        $distribution = Distribution::factory()->create();

        $this->patchJson("/api/distributions/{$distribution->id}", [
            'status' => 'live',
            'live_at' => '2026-01-15',
        ])->assertStatus(200)
          ->assertJsonPath('data.status', 'live');
    }

    public function test_ar_cannot_update_distribution(): void
    {
        Sanctum::actingAs($this->userWithRole('ar'));
        $distribution = Distribution::factory()->create();

        $this->patchJson("/api/distributions/{$distribution->id}", [
            'status' => 'live',
        ])->assertStatus(403);
    }

    public function test_artist_manager_cannot_update_distribution(): void
    {
        Sanctum::actingAs($this->userWithRole('artist-manager'));
        $distribution = Distribution::factory()->create();

        $this->patchJson("/api/distributions/{$distribution->id}", [
            'status' => 'live',
        ])->assertStatus(403);
    }

    public function test_super_admin_can_update_any_distribution(): void
    {
        Sanctum::actingAs($this->userWithRole('super-admin'));
        $distribution = Distribution::factory()->create();

        $this->patchJson("/api/distributions/{$distribution->id}", [
            'status' => 'takedown',
        ])->assertStatus(200);
    }

    // ---------------------------------------------------------------
    // Delete authorization + soft delete
    // ---------------------------------------------------------------

    public function test_label_manager_can_soft_delete_distribution(): void
    {
        Sanctum::actingAs($this->userWithRole('label-manager'));
        $distribution = Distribution::factory()->create();

        $this->deleteJson("/api/distributions/{$distribution->id}")->assertStatus(200);

        $this->assertSoftDeleted('distributions', ['id' => $distribution->id]);
    }

    public function test_super_admin_can_soft_delete_distribution(): void
    {
        Sanctum::actingAs($this->userWithRole('super-admin'));
        $distribution = Distribution::factory()->create();

        $this->deleteJson("/api/distributions/{$distribution->id}")->assertStatus(200);
        $this->assertSoftDeleted('distributions', ['id' => $distribution->id]);
    }

    public function test_distribution_manager_cannot_delete_distribution(): void
    {
        Sanctum::actingAs($this->userWithRole('distribution-manager'));
        $distribution = Distribution::factory()->create();

        $this->deleteJson("/api/distributions/{$distribution->id}")->assertStatus(403);
        $this->assertDatabaseHas('distributions', [
            'id' => $distribution->id,
            'deleted_at' => null,
        ]);
    }

    public function test_ar_cannot_delete_distribution(): void
    {
        Sanctum::actingAs($this->userWithRole('ar'));
        $distribution = Distribution::factory()->create();

        $this->deleteJson("/api/distributions/{$distribution->id}")->assertStatus(403);
    }

    // ---------------------------------------------------------------
    // Release relationship + soft-deleted release behavior
    // ---------------------------------------------------------------

    public function test_distribution_belongs_to_release(): void
    {
        $release = Release::factory()->create();
        $distribution = Distribution::factory()->create(['release_id' => $release->id]);

        $this->assertEquals($release->id, $distribution->release->id);
    }

    public function test_distribution_remains_queryable_when_release_is_soft_deleted(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));

        $release = Release::factory()->create();
        $distribution = Distribution::factory()->create(['release_id' => $release->id]);

        $release->delete();

        $this->assertDatabaseHas('distributions', [
            'id' => $distribution->id,
            'deleted_at' => null,
        ]);

        $this->getJson("/api/distributions/{$distribution->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $distribution->id);
    }

    // ---------------------------------------------------------------
    // Filters
    // ---------------------------------------------------------------

    public function test_search_filter(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));

        Distribution::factory()->create(['distributor' => 'Findme Distro']);
        Distribution::factory()->create(['distributor' => 'Other One']);

        $response = $this->getJson('/api/distributions?search=Findme')->assertStatus(200);

        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Findme Distro', $response->json('data.0.distributor'));
    }

  public function test_release_id_filter(): void
{
    Sanctum::actingAs($this->userWithRole('general-staff'));

    $releaseA = Release::factory()->create();
    $releaseB = Release::factory()->create();

    // Create two distributions on releaseA with EXPLICIT distinct platforms
    // so the factory's random platform pick can't produce a unique-constraint collision.
    Distribution::factory()->create(['release_id' => $releaseA->id, 'platform' => 'spotify']);
    Distribution::factory()->create(['release_id' => $releaseA->id, 'platform' => 'apple']);
    Distribution::factory()->create(['release_id' => $releaseB->id]);

    $response = $this->getJson("/api/distributions?release_id={$releaseA->id}")->assertStatus(200);

    $this->assertCount(2, $response->json('data'));
}

    public function test_platform_filter(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));

        Distribution::factory()->count(2)->create(['platform' => 'spotify']);
        Distribution::factory()->create(['platform' => 'tidal']);

        $response = $this->getJson('/api/distributions?platform=spotify')->assertStatus(200);

        $this->assertCount(2, $response->json('data'));
    }

    public function test_status_filter(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));

        Distribution::factory()->count(3)->create(['status' => 'live']);
        Distribution::factory()->create(['status' => 'pending']);

        $response = $this->getJson('/api/distributions?status=live')->assertStatus(200);

        $this->assertCount(3, $response->json('data'));
    }

    public function test_distributor_filter(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));

        Distribution::factory()->count(2)->create(['distributor' => 'Empire']);
        Distribution::factory()->create(['distributor' => 'TuneCore']);

        $response = $this->getJson('/api/distributions?distributor=Empire')->assertStatus(200);

        $this->assertCount(2, $response->json('data'));
    }

    public function test_pagination(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));

        // Need unique (release_id, platform) pairs; use 25 releases.
        for ($i = 0; $i < 25; $i++) {
            Distribution::factory()->create([
                'release_id' => Release::factory()->create()->id,
                'platform' => 'spotify',
            ]);
        }

        $response = $this->getJson('/api/distributions?per_page=10')->assertStatus(200);

        $this->assertCount(10, $response->json('data'));
        $this->assertEquals(25, $response->json('meta.total'));
        $this->assertEquals(10, $response->json('meta.per_page'));
    }

    // ---------------------------------------------------------------
    // Validation
    // ---------------------------------------------------------------

    public function test_missing_required_fields_returns_422(): void
    {
        Sanctum::actingAs($this->userWithRole('distribution-manager'));

        $this->postJson('/api/distributions', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['release_id', 'platform']);
    }

    public function test_invalid_platform_returns_422(): void
    {
        Sanctum::actingAs($this->userWithRole('distribution-manager'));
        $release = Release::factory()->create();

        $this->postJson('/api/distributions', [
            'release_id' => $release->id,
            'platform' => 'napster',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['platform']);
    }

    public function test_invalid_status_returns_422(): void
    {
        Sanctum::actingAs($this->userWithRole('distribution-manager'));
        $release = Release::factory()->create();

        $this->postJson('/api/distributions', [
            'release_id' => $release->id,
            'platform' => 'spotify',
            'status' => 'bogus',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['status']);
    }

    public function test_invalid_release_id_returns_422(): void
    {
        Sanctum::actingAs($this->userWithRole('distribution-manager'));

        $this->postJson('/api/distributions', [
            'release_id' => 99999,
            'platform' => 'spotify',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['release_id']);
    }

    public function test_invalid_platform_url_returns_422(): void
    {
        Sanctum::actingAs($this->userWithRole('distribution-manager'));
        $release = Release::factory()->create();

        $this->postJson('/api/distributions', [
            'release_id' => $release->id,
            'platform' => 'spotify',
            'platform_url' => 'not-a-url',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['platform_url']);
    }
}