<?php

namespace Tests\Feature;

use App\Models\Artist;
use App\Models\Contract;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ContractTest extends TestCase
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
        $this->getJson('/api/contracts')->assertStatus(401);
        $this->postJson('/api/contracts', [])->assertStatus(401);
    }

    // ---------------------------------------------------------------
    // View / Index
    // ---------------------------------------------------------------

    public function test_any_authenticated_role_can_list_contracts(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));
        Contract::factory()->count(3)->create();

        $this->getJson('/api/contracts')
            ->assertStatus(200)
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_show_returns_single_contract(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));
        $contract = Contract::factory()->create();

        $this->getJson("/api/contracts/{$contract->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $contract->id);
    }

    // ---------------------------------------------------------------
    // Create authorization
    // ---------------------------------------------------------------

    public function test_super_admin_can_create_contract(): void
    {
        Sanctum::actingAs($this->userWithRole('super-admin'));
        $artist = Artist::factory()->create();

        $this->postJson('/api/contracts', [
            'artist_id' => $artist->id,
            'title' => 'Test Agreement',
            'type' => Contract::TYPE_RECORDING_AGREEMENT,
        ])->assertStatus(201);
    }

    public function test_label_manager_can_create_contract(): void
    {
        Sanctum::actingAs($this->userWithRole('label-manager'));
        $artist = Artist::factory()->create();

        $this->postJson('/api/contracts', [
            'artist_id' => $artist->id,
            'title' => 'LM Agreement',
            'type' => Contract::TYPE_ARTIST_AGREEMENT,
        ])->assertStatus(201);
    }

    public function test_ar_can_create_contract(): void
    {
        Sanctum::actingAs($this->userWithRole('ar'));
        $artist = Artist::factory()->create();

        $this->postJson('/api/contracts', [
            'artist_id' => $artist->id,
            'title' => 'AR Agreement',
            'type' => Contract::TYPE_DISTRIBUTION_AGREEMENT,
        ])->assertStatus(201);
    }

    public function test_artist_manager_cannot_create_contract(): void
    {
        Sanctum::actingAs($this->userWithRole('artist-manager'));
        $artist = Artist::factory()->create();

        $this->postJson('/api/contracts', [
            'artist_id' => $artist->id,
            'title' => 'Blocked',
            'type' => Contract::TYPE_RECORDING_AGREEMENT,
        ])->assertStatus(403);
    }

    public function test_marketing_staff_cannot_create_contract(): void
    {
        Sanctum::actingAs($this->userWithRole('marketing-staff'));
        $artist = Artist::factory()->create();

        $this->postJson('/api/contracts', [
            'artist_id' => $artist->id,
            'title' => 'Blocked',
            'type' => Contract::TYPE_RECORDING_AGREEMENT,
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

        $this->postJson('/api/contracts', [
            'artist_id' => $artist->id,
            'title' => 'Spoof Test',
            'type' => Contract::TYPE_OTHER,
            'created_by' => $other->id,
        ])->assertStatus(201);

        $contract = Contract::where('title', 'Spoof Test')->first();
        $this->assertEquals($creator->id, $contract->created_by);
        $this->assertNotEquals($other->id, $contract->created_by);
    }

    public function test_contract_code_is_auto_generated_and_unique(): void
    {
        Sanctum::actingAs($this->userWithRole('label-manager'));
        $artist = Artist::factory()->create();

        $this->postJson('/api/contracts', [
            'artist_id' => $artist->id,
            'title' => 'C1',
            'type' => Contract::TYPE_OTHER,
        ])->assertStatus(201);

        $this->postJson('/api/contracts', [
            'artist_id' => $artist->id,
            'title' => 'C2',
            'type' => Contract::TYPE_OTHER,
        ])->assertStatus(201);

        $codes = Contract::pluck('contract_code')->all();

        $this->assertCount(2, $codes);
        $this->assertCount(2, array_unique($codes));

        foreach ($codes as $code) {
            $this->assertMatchesRegularExpression('/^KFM-CON-\d{4}$/', $code);
        }
    }

    // ---------------------------------------------------------------
    // Update authorization
    // ---------------------------------------------------------------

    public function test_label_manager_can_update_contract(): void
    {
        Sanctum::actingAs($this->userWithRole('label-manager'));
        $contract = Contract::factory()->create();

        $this->putJson("/api/contracts/{$contract->id}", ['title' => 'Updated'])
            ->assertStatus(200)
            ->assertJsonPath('data.title', 'Updated');
    }

    public function test_super_admin_can_update_any_contract(): void
    {
        Sanctum::actingAs($this->userWithRole('super-admin'));
        $contract = Contract::factory()->create();

        $this->patchJson("/api/contracts/{$contract->id}", ['status' => Contract::STATUS_ACTIVE])
            ->assertStatus(200);
    }

    public function test_ar_cannot_update_contract(): void
    {
        Sanctum::actingAs($this->userWithRole('ar'));
        $contract = Contract::factory()->create();

        $this->patchJson("/api/contracts/{$contract->id}", ['status' => Contract::STATUS_ACTIVE])
            ->assertStatus(403);
    }

    public function test_artist_manager_cannot_update_contract(): void
    {
        Sanctum::actingAs($this->userWithRole('artist-manager'));
        $contract = Contract::factory()->create();

        $this->patchJson("/api/contracts/{$contract->id}", ['status' => Contract::STATUS_ACTIVE])
            ->assertStatus(403);
    }

    // ---------------------------------------------------------------
    // Delete authorization + soft delete
    // ---------------------------------------------------------------

    public function test_label_manager_can_soft_delete_contract(): void
    {
        Sanctum::actingAs($this->userWithRole('label-manager'));
        $contract = Contract::factory()->create();

        $this->deleteJson("/api/contracts/{$contract->id}")->assertStatus(200);

        $this->assertSoftDeleted('contracts', ['id' => $contract->id]);
    }

    public function test_super_admin_can_soft_delete_contract(): void
    {
        Sanctum::actingAs($this->userWithRole('super-admin'));
        $contract = Contract::factory()->create();

        $this->deleteJson("/api/contracts/{$contract->id}")->assertStatus(200);
        $this->assertSoftDeleted('contracts', ['id' => $contract->id]);
    }

    public function test_ar_cannot_delete_contract(): void
    {
        Sanctum::actingAs($this->userWithRole('ar'));
        $contract = Contract::factory()->create();

        $this->deleteJson("/api/contracts/{$contract->id}")->assertStatus(403);
        $this->assertDatabaseHas('contracts', ['id' => $contract->id, 'deleted_at' => null]);
    }

    // ---------------------------------------------------------------
    // Artist relationship + soft-deleted artist behavior
    // ---------------------------------------------------------------

    public function test_contract_belongs_to_artist(): void
    {
        $artist = Artist::factory()->create();
        $contract = Contract::factory()->create(['artist_id' => $artist->id]);

        $this->assertEquals($artist->id, $contract->artist->id);
    }

    public function test_contract_remains_queryable_when_artist_is_soft_deleted(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));

        $artist = Artist::factory()->create();
        $contract = Contract::factory()->create(['artist_id' => $artist->id]);

        $artist->delete();

        // Contract itself is untouched
        $this->assertDatabaseHas('contracts', ['id' => $contract->id, 'deleted_at' => null]);

        // Still visible through API
        $this->getJson("/api/contracts/{$contract->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $contract->id);
    }

    // ---------------------------------------------------------------
    // Filters
    // ---------------------------------------------------------------

    public function test_search_filter(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));

        Contract::factory()->create(['title' => 'Findme Agreement']);
        Contract::factory()->create(['title' => 'Other One']);

        $response = $this->getJson('/api/contracts?search=Findme')->assertStatus(200);

        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Findme Agreement', $response->json('data.0.title'));
    }

    public function test_status_filter(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));

        Contract::factory()->count(2)->create(['status' => Contract::STATUS_ACTIVE]);
        Contract::factory()->create(['status' => Contract::STATUS_DRAFT]);

        $response = $this->getJson('/api/contracts?status=active')->assertStatus(200);

        $this->assertCount(2, $response->json('data'));
    }

    public function test_type_filter(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));

        Contract::factory()->count(3)->create(['type' => Contract::TYPE_RECORDING_AGREEMENT]);
        Contract::factory()->create(['type' => Contract::TYPE_OTHER]);

        $response = $this->getJson('/api/contracts?type=recording_agreement')->assertStatus(200);

        $this->assertCount(3, $response->json('data'));
    }

    public function test_artist_id_filter(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));

        $artistA = Artist::factory()->create();
        $artistB = Artist::factory()->create();

        Contract::factory()->count(2)->create(['artist_id' => $artistA->id]);
        Contract::factory()->create(['artist_id' => $artistB->id]);

        $response = $this->getJson("/api/contracts?artist_id={$artistA->id}")->assertStatus(200);

        $this->assertCount(2, $response->json('data'));
    }

    public function test_pagination(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));
        Contract::factory()->count(25)->create();

        $response = $this->getJson('/api/contracts?per_page=10')->assertStatus(200);

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

        $this->postJson('/api/contracts', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['artist_id', 'title', 'type']);
    }

    public function test_invalid_type_returns_422(): void
    {
        Sanctum::actingAs($this->userWithRole('label-manager'));
        $artist = Artist::factory()->create();

        $this->postJson('/api/contracts', [
            'artist_id' => $artist->id,
            'title' => 'X',
            'type' => 'bogus_type',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['type']);
    }

    public function test_invalid_status_returns_422(): void
    {
        Sanctum::actingAs($this->userWithRole('label-manager'));
        $artist = Artist::factory()->create();

        $this->postJson('/api/contracts', [
            'artist_id' => $artist->id,
            'title' => 'X',
            'type' => Contract::TYPE_OTHER,
            'status' => 'activeX',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['status']);
    }

    public function test_invalid_artist_id_returns_422(): void
    {
        Sanctum::actingAs($this->userWithRole('label-manager'));

        $this->postJson('/api/contracts', [
            'artist_id' => 99999,
            'title' => 'X',
            'type' => Contract::TYPE_OTHER,
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['artist_id']);
    }

    public function test_royalty_rate_out_of_range_returns_422(): void
    {
        Sanctum::actingAs($this->userWithRole('label-manager'));
        $artist = Artist::factory()->create();

        $this->postJson('/api/contracts', [
            'artist_id' => $artist->id,
            'title' => 'X',
            'type' => Contract::TYPE_OTHER,
            'royalty_rate' => 150,
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['royalty_rate']);
    }

    public function test_end_date_before_start_date_returns_422(): void
    {
        Sanctum::actingAs($this->userWithRole('label-manager'));
        $artist = Artist::factory()->create();

        $this->postJson('/api/contracts', [
            'artist_id' => $artist->id,
            'title' => 'X',
            'type' => Contract::TYPE_OTHER,
            'start_date' => '2026-01-01',
            'end_date' => '2025-01-01',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['end_date']);
    }
}