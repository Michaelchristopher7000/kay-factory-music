<?php

namespace Tests\Feature;

use App\Models\Artist;
use App\Models\Distribution;
use App\Models\Release;
use App\Models\RevenueEntry;
use App\Models\Role;
use App\Models\Track;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RevenueEntryTest extends TestCase
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
        $this->getJson('/api/revenue-entries')->assertStatus(401);
        $this->postJson('/api/revenue-entries', [])->assertStatus(401);
    }

    // ---------------------------------------------------------------
    // View
    // ---------------------------------------------------------------

    public function test_any_authenticated_role_can_list_revenue_entries(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));
        RevenueEntry::factory()->count(3)->create();

        $this->getJson('/api/revenue-entries')
            ->assertStatus(200)
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_show_returns_single_revenue_entry(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));
        $entry = RevenueEntry::factory()->create();

        $this->getJson("/api/revenue-entries/{$entry->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $entry->id);
    }

    // ---------------------------------------------------------------
    // Create authorization
    // ---------------------------------------------------------------

    public function test_super_admin_can_create_revenue_entry(): void
    {
        Sanctum::actingAs($this->userWithRole('super-admin'));

        $this->postJson('/api/revenue-entries', [
            'source' => 'streaming',
            'amount' => 1500.00,
            'currency' => 'NGN',
        ])->assertStatus(201);
    }

    public function test_label_manager_can_create_revenue_entry(): void
    {
        Sanctum::actingAs($this->userWithRole('label-manager'));

        $this->postJson('/api/revenue-entries', [
            'source' => 'sync',
            'amount' => 2500.00,
            'currency' => 'USD',
        ])->assertStatus(201);
    }

    public function test_finance_staff_can_create_revenue_entry(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));

        $this->postJson('/api/revenue-entries', [
            'source' => 'streaming',
            'amount' => 800.00,
            'currency' => 'NGN',
        ])->assertStatus(201);
    }

    public function test_ar_cannot_create_revenue_entry(): void
    {
        Sanctum::actingAs($this->userWithRole('ar'));

        $this->postJson('/api/revenue-entries', [
            'source' => 'streaming',
            'amount' => 100,
            'currency' => 'NGN',
        ])->assertStatus(403);
    }

    public function test_marketing_staff_cannot_create_revenue_entry(): void
    {
        Sanctum::actingAs($this->userWithRole('marketing-staff'));

        $this->postJson('/api/revenue-entries', [
            'source' => 'streaming',
            'amount' => 100,
            'currency' => 'NGN',
        ])->assertStatus(403);
    }

    // ---------------------------------------------------------------
    // Create side-effects
    // ---------------------------------------------------------------

    public function test_created_by_is_taken_from_authenticated_user(): void
    {
        $creator = $this->userWithRole('finance-staff');
        $other = $this->userWithRole('finance-staff');
        Sanctum::actingAs($creator);

        $this->postJson('/api/revenue-entries', [
            'source' => 'streaming',
            'amount' => 500,
            'currency' => 'NGN',
            'created_by' => $other->id,
        ])->assertStatus(201);

        $entry = RevenueEntry::latest('id')->first();
        $this->assertEquals($creator->id, $entry->created_by);
        $this->assertNotEquals($other->id, $entry->created_by);
    }

    public function test_entry_code_is_auto_generated_and_unique(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));

        $this->postJson('/api/revenue-entries', [
            'source' => 'streaming',
            'amount' => 100,
            'currency' => 'NGN',
        ])->assertStatus(201);

        $this->postJson('/api/revenue-entries', [
            'source' => 'sync',
            'amount' => 200,
            'currency' => 'USD',
        ])->assertStatus(201);

        $codes = RevenueEntry::pluck('entry_code')->all();

        $this->assertCount(2, $codes);
        $this->assertCount(2, array_unique($codes));

        foreach ($codes as $code) {
            $this->assertMatchesRegularExpression('/^KFM-REV-\d{4}$/', $code);
        }
    }

    // ---------------------------------------------------------------
    // Optional attributions
    // ---------------------------------------------------------------

    public function test_entry_can_be_created_without_attribution(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));

        $response = $this->postJson('/api/revenue-entries', [
            'source' => 'merch',
            'amount' => 300,
            'currency' => 'NGN',
        ])->assertStatus(201);

        $response
            ->assertJsonPath('data.artist_id', null)
            ->assertJsonPath('data.release_id', null)
            ->assertJsonPath('data.track_id', null)
            ->assertJsonPath('data.distribution_id', null);
    }

    public function test_entry_can_link_to_full_chain(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));

        $artist = Artist::factory()->create();
        $release = Release::factory()->create(['artist_id' => $artist->id]);
        $track = Track::factory()->create(['artist_id' => $artist->id]);
        $distribution = Distribution::factory()->create(['release_id' => $release->id]);

        $this->postJson('/api/revenue-entries', [
            'source' => 'streaming',
            'artist_id' => $artist->id,
            'release_id' => $release->id,
            'track_id' => $track->id,
            'distribution_id' => $distribution->id,
            'amount' => 1000,
            'currency' => 'NGN',
        ])->assertStatus(201);

        $entry = RevenueEntry::latest('id')->first();
        $this->assertEquals($artist->id, $entry->artist_id);
        $this->assertEquals($release->id, $entry->release_id);
        $this->assertEquals($track->id, $entry->track_id);
        $this->assertEquals($distribution->id, $entry->distribution_id);
    }

    // ---------------------------------------------------------------
    // Update authorization
    // ---------------------------------------------------------------

    public function test_label_manager_can_update_revenue_entry(): void
    {
        Sanctum::actingAs($this->userWithRole('label-manager'));
        $entry = RevenueEntry::factory()->create();

        $this->putJson("/api/revenue-entries/{$entry->id}", ['amount' => 9999])
            ->assertStatus(200)
            ->assertJsonPath('data.amount', '9999.00');
    }

    public function test_finance_staff_can_update_revenue_entry(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));
        $entry = RevenueEntry::factory()->create();

        $this->patchJson("/api/revenue-entries/{$entry->id}", ['notes' => 'adjusted'])
            ->assertStatus(200);
    }

    public function test_ar_cannot_update_revenue_entry(): void
    {
        Sanctum::actingAs($this->userWithRole('ar'));
        $entry = RevenueEntry::factory()->create();

        $this->patchJson("/api/revenue-entries/{$entry->id}", ['amount' => 1])
            ->assertStatus(403);
    }

    // ---------------------------------------------------------------
    // Delete authorization + soft delete
    // ---------------------------------------------------------------

    public function test_label_manager_can_soft_delete_revenue_entry(): void
    {
        Sanctum::actingAs($this->userWithRole('label-manager'));
        $entry = RevenueEntry::factory()->create();

        $this->deleteJson("/api/revenue-entries/{$entry->id}")->assertStatus(200);

        $this->assertSoftDeleted('revenue_entries', ['id' => $entry->id]);
    }

    public function test_finance_staff_cannot_delete_revenue_entry(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));
        $entry = RevenueEntry::factory()->create();

        $this->deleteJson("/api/revenue-entries/{$entry->id}")->assertStatus(403);
        $this->assertDatabaseHas('revenue_entries', ['id' => $entry->id, 'deleted_at' => null]);
    }

    // ---------------------------------------------------------------
    // FK survives soft delete of linked entities
    // ---------------------------------------------------------------

    public function test_entry_survives_artist_soft_delete(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));

        $artist = Artist::factory()->create();
        $entry = RevenueEntry::factory()->create(['artist_id' => $artist->id]);

        $artist->delete();

        $this->assertDatabaseHas('revenue_entries', ['id' => $entry->id, 'deleted_at' => null]);

        $this->getJson("/api/revenue-entries/{$entry->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $entry->id);
    }

    public function test_entry_survives_distribution_soft_delete(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));

        $distribution = Distribution::factory()->create();
        $entry = RevenueEntry::factory()->create(['distribution_id' => $distribution->id]);

        $distribution->delete();

        $this->assertDatabaseHas('revenue_entries', ['id' => $entry->id, 'deleted_at' => null]);
    }

    // ---------------------------------------------------------------
    // Filters
    // ---------------------------------------------------------------

    public function test_search_filter(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));

        RevenueEntry::factory()->create(['reference' => 'FINDME-REF']);
        RevenueEntry::factory()->create(['reference' => 'OTHER-REF']);

        $response = $this->getJson('/api/revenue-entries?search=FINDME')->assertStatus(200);

        $this->assertCount(1, $response->json('data'));
    }

    public function test_source_filter(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));

        RevenueEntry::factory()->count(2)->create(['source' => 'streaming']);
        RevenueEntry::factory()->create(['source' => 'sync']);

        $response = $this->getJson('/api/revenue-entries?source=streaming')->assertStatus(200);

        $this->assertCount(2, $response->json('data'));
    }

    public function test_currency_filter(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));

        RevenueEntry::factory()->count(2)->create(['currency' => 'NGN']);
        RevenueEntry::factory()->create(['currency' => 'USD']);

        $response = $this->getJson('/api/revenue-entries?currency=ngn')->assertStatus(200);

        $this->assertCount(2, $response->json('data'));
    }

    public function test_date_range_filter(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));

        RevenueEntry::factory()->create(['period_end' => '2026-03-31']);
        RevenueEntry::factory()->create(['period_end' => '2026-06-30']);
        RevenueEntry::factory()->create(['period_end' => '2026-09-30']);

        $response = $this->getJson('/api/revenue-entries?from=2026-04-01&to=2026-08-31')
            ->assertStatus(200);

        $this->assertCount(1, $response->json('data'));
    }

    public function test_distribution_id_filter(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));

        $dist = Distribution::factory()->create();
        RevenueEntry::factory()->count(2)->create(['distribution_id' => $dist->id]);
        RevenueEntry::factory()->create(['distribution_id' => null]);

        $response = $this->getJson("/api/revenue-entries?distribution_id={$dist->id}")
            ->assertStatus(200);

        $this->assertCount(2, $response->json('data'));
    }

    public function test_pagination(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));
        RevenueEntry::factory()->count(25)->create();

        $response = $this->getJson('/api/revenue-entries?per_page=10')->assertStatus(200);

        $this->assertCount(10, $response->json('data'));
        $this->assertEquals(25, $response->json('meta.total'));
    }

    // ---------------------------------------------------------------
    // Validation
    // ---------------------------------------------------------------

    public function test_missing_required_fields_returns_422(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));

        $this->postJson('/api/revenue-entries', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['source', 'amount', 'currency']);
    }

    public function test_invalid_source_returns_422(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));

        $this->postJson('/api/revenue-entries', [
            'source' => 'bogus',
            'amount' => 100,
            'currency' => 'NGN',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['source']);
    }

    public function test_zero_amount_returns_422(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));

        $this->postJson('/api/revenue-entries', [
            'source' => 'streaming',
            'amount' => 0,
            'currency' => 'NGN',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['amount']);
    }

    public function test_negative_amount_returns_422(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));

        $this->postJson('/api/revenue-entries', [
            'source' => 'streaming',
            'amount' => -50,
            'currency' => 'NGN',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['amount']);
    }

    public function test_invalid_currency_length_returns_422(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));

        $this->postJson('/api/revenue-entries', [
            'source' => 'streaming',
            'amount' => 100,
            'currency' => 'NIGERIA',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['currency']);
    }

    public function test_invalid_fk_returns_422(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));

        $this->postJson('/api/revenue-entries', [
            'source' => 'streaming',
            'amount' => 100,
            'currency' => 'NGN',
            'artist_id' => 99999,
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['artist_id']);
    }

    public function test_period_end_before_start_returns_422(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));

        $this->postJson('/api/revenue-entries', [
            'source' => 'streaming',
            'amount' => 100,
            'currency' => 'NGN',
            'period_start' => '2026-06-01',
            'period_end' => '2026-01-01',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['period_end']);
    }
}