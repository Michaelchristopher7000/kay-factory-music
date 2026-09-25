<?php

namespace Tests\Feature;

use App\Models\Artist;
use App\Models\Contract;
use App\Models\RevenueEntry;
use App\Models\Role;
use App\Models\RoyaltyStatement;
use App\Models\RoyaltyStatementLine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RoyaltyStatementTest extends TestCase
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

    protected function revenueEntry(Artist $artist, array $overrides = []): RevenueEntry
    {
        return RevenueEntry::factory()->create(array_merge([
            'artist_id' => $artist->id,
            'currency' => 'NGN',
            'amount' => 10000,
            'period_end' => '2026-06-15',
            'period_start' => '2026-05-16',
            'source' => 'streaming',
        ], $overrides));
    }

    // ---------------------------------------------------------------
    // Auth
    // ---------------------------------------------------------------

    public function test_unauthenticated_request_returns_401(): void
    {
        $this->getJson('/api/royalty-statements')->assertStatus(401);
        $this->postJson('/api/royalty-statements', [])->assertStatus(401);
    }

    // ---------------------------------------------------------------
    // View
    // ---------------------------------------------------------------

    public function test_any_role_can_list_statements(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));
        RoyaltyStatement::factory()->count(3)->create();

        $this->getJson('/api/royalty-statements')
            ->assertStatus(200)
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_show_returns_statement_with_lines_key(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));
        $statement = RoyaltyStatement::factory()->create();

        $this->getJson("/api/royalty-statements/{$statement->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $statement->id)
            ->assertJsonStructure(['data' => ['id', 'statement_code', 'lines']]);
    }

    // ---------------------------------------------------------------
    // Create authorization
    // ---------------------------------------------------------------

    public function test_super_admin_can_create_statement(): void
    {
        Sanctum::actingAs($this->userWithRole('super-admin'));
        $artist = Artist::factory()->create();

        $this->postJson('/api/royalty-statements', [
            'artist_id' => $artist->id,
            'period_start' => '2026-01-01',
            'period_end' => '2026-01-31',
            'currency' => 'NGN',
            'generate' => false,
        ])->assertStatus(201);
    }

    public function test_label_manager_can_create_statement(): void
    {
        Sanctum::actingAs($this->userWithRole('label-manager'));
        $artist = Artist::factory()->create();

        $this->postJson('/api/royalty-statements', [
            'artist_id' => $artist->id,
            'period_start' => '2026-01-01',
            'period_end' => '2026-01-31',
            'currency' => 'NGN',
            'generate' => false,
        ])->assertStatus(201);
    }

    public function test_finance_staff_can_create_statement(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));
        $artist = Artist::factory()->create();

        $this->postJson('/api/royalty-statements', [
            'artist_id' => $artist->id,
            'period_start' => '2026-01-01',
            'period_end' => '2026-01-31',
            'currency' => 'NGN',
            'generate' => false,
        ])->assertStatus(201);
    }

    public function test_ar_cannot_create_statement(): void
    {
        Sanctum::actingAs($this->userWithRole('ar'));
        $artist = Artist::factory()->create();

        $this->postJson('/api/royalty-statements', [
            'artist_id' => $artist->id,
            'period_start' => '2026-01-01',
            'period_end' => '2026-01-31',
            'currency' => 'NGN',
        ])->assertStatus(403);
    }

    // ---------------------------------------------------------------
    // Create side-effects
    // ---------------------------------------------------------------

    public function test_created_by_is_taken_from_auth_user(): void
    {
        $creator = $this->userWithRole('finance-staff');
        $other = $this->userWithRole('finance-staff');
        Sanctum::actingAs($creator);
        $artist = Artist::factory()->create();

        $this->postJson('/api/royalty-statements', [
            'artist_id' => $artist->id,
            'period_start' => '2026-01-01',
            'period_end' => '2026-01-31',
            'currency' => 'NGN',
            'created_by' => $other->id,
            'generate' => false,
        ])->assertStatus(201);

        $statement = RoyaltyStatement::latest('id')->first();
        $this->assertEquals($creator->id, $statement->created_by);
        $this->assertNotEquals($other->id, $statement->created_by);
    }

    public function test_statement_code_auto_generated_and_unique(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));
        $artist = Artist::factory()->create();

        $this->postJson('/api/royalty-statements', [
            'artist_id' => $artist->id,
            'period_start' => '2026-01-01',
            'period_end' => '2026-01-31',
            'currency' => 'NGN',
            'generate' => false,
        ])->assertStatus(201);

        $this->postJson('/api/royalty-statements', [
            'artist_id' => $artist->id,
            'period_start' => '2026-02-01',
            'period_end' => '2026-02-28',
            'currency' => 'NGN',
            'generate' => false,
        ])->assertStatus(201);

        $codes = RoyaltyStatement::pluck('statement_code')->all();
        $this->assertCount(2, $codes);
        $this->assertCount(2, array_unique($codes));

        foreach ($codes as $code) {
            $this->assertMatchesRegularExpression('/^KFM-STM-\d{4}$/', $code);
        }
    }

    // ---------------------------------------------------------------
    // Rate resolution
    // ---------------------------------------------------------------

    public function test_rate_uses_request_value_when_provided(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));
        $artist = Artist::factory()->create();

        $this->postJson('/api/royalty-statements', [
            'artist_id' => $artist->id,
            'period_start' => '2026-01-01',
            'period_end' => '2026-01-31',
            'currency' => 'NGN',
            'royalty_rate' => 75,
            'generate' => false,
        ])->assertStatus(201)
          ->assertJsonPath('data.royalty_rate', '75.00');
    }

    public function test_rate_uses_latest_active_contract_when_not_provided(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));
        $artist = Artist::factory()->create();

        Contract::factory()->create([
            'artist_id' => $artist->id,
            'status' => Contract::STATUS_ACTIVE,
            'royalty_rate' => 60,
        ]);

        $this->postJson('/api/royalty-statements', [
            'artist_id' => $artist->id,
            'period_start' => '2026-01-01',
            'period_end' => '2026-01-31',
            'currency' => 'NGN',
            'generate' => false,
        ])->assertStatus(201)
          ->assertJsonPath('data.royalty_rate', '60.00');
    }

    public function test_rate_defaults_to_zero_when_no_active_contract(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));
        $artist = Artist::factory()->create();

        $this->postJson('/api/royalty-statements', [
            'artist_id' => $artist->id,
            'period_start' => '2026-01-01',
            'period_end' => '2026-01-31',
            'currency' => 'NGN',
            'generate' => false,
        ])->assertStatus(201)
          ->assertJsonPath('data.royalty_rate', '0.00');
    }

    // ---------------------------------------------------------------
    // Line generation
    // ---------------------------------------------------------------

    public function test_generate_true_creates_lines_from_matching_revenue(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));
        $artist = Artist::factory()->create();

        $this->revenueEntry($artist, ['amount' => 10000, 'period_end' => '2026-06-15']);
        $this->revenueEntry($artist, ['amount' => 20000, 'period_end' => '2026-06-20']);

        $response = $this->postJson('/api/royalty-statements', [
            'artist_id' => $artist->id,
            'period_start' => '2026-06-01',
            'period_end' => '2026-06-30',
            'currency' => 'NGN',
            'royalty_rate' => 50,
            'generate' => true,
        ])->assertStatus(201);

        $data = $response->json('data');
        $this->assertCount(2, $data['lines']);
        $this->assertEquals('30000.00', $data['total_revenue']);
        $this->assertEquals('15000.00', $data['total_royalty']);
    }

    public function test_generate_false_creates_no_lines(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));
        $artist = Artist::factory()->create();
        $this->revenueEntry($artist);

        $response = $this->postJson('/api/royalty-statements', [
            'artist_id' => $artist->id,
            'period_start' => '2026-06-01',
            'period_end' => '2026-06-30',
            'currency' => 'NGN',
            'royalty_rate' => 50,
            'generate' => false,
        ])->assertStatus(201);

        $this->assertCount(0, $response->json('data.lines'));
    }

    public function test_lines_only_include_matching_currency(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));
        $artist = Artist::factory()->create();

        $this->revenueEntry($artist, ['currency' => 'NGN', 'period_end' => '2026-06-15']);
        $this->revenueEntry($artist, ['currency' => 'USD', 'period_end' => '2026-06-15']);

        $response = $this->postJson('/api/royalty-statements', [
            'artist_id' => $artist->id,
            'period_start' => '2026-06-01',
            'period_end' => '2026-06-30',
            'currency' => 'NGN',
            'royalty_rate' => 50,
            'generate' => true,
        ])->assertStatus(201);

        $this->assertCount(1, $response->json('data.lines'));
    }

    public function test_lines_only_include_matching_period(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));
        $artist = Artist::factory()->create();

        $this->revenueEntry($artist, ['period_end' => '2026-06-15']);
        $this->revenueEntry($artist, ['period_end' => '2026-07-15']);

        $response = $this->postJson('/api/royalty-statements', [
            'artist_id' => $artist->id,
            'period_start' => '2026-06-01',
            'period_end' => '2026-06-30',
            'currency' => 'NGN',
            'royalty_rate' => 50,
            'generate' => true,
        ])->assertStatus(201);

        $this->assertCount(1, $response->json('data.lines'));
    }

    public function test_lines_only_include_matching_artist(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));
        $artistA = Artist::factory()->create();
        $artistB = Artist::factory()->create();

        $this->revenueEntry($artistA, ['period_end' => '2026-06-15']);
        $this->revenueEntry($artistB, ['period_end' => '2026-06-15']);

        $response = $this->postJson('/api/royalty-statements', [
            'artist_id' => $artistA->id,
            'period_start' => '2026-06-01',
            'period_end' => '2026-06-30',
            'currency' => 'NGN',
            'royalty_rate' => 50,
            'generate' => true,
        ])->assertStatus(201);

        $this->assertCount(1, $response->json('data.lines'));
    }

    public function test_royalty_amount_is_computed_correctly(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));
        $artist = Artist::factory()->create();

        $this->revenueEntry($artist, ['amount' => 10000, 'period_end' => '2026-06-15']);

        $response = $this->postJson('/api/royalty-statements', [
            'artist_id' => $artist->id,
            'period_start' => '2026-06-01',
            'period_end' => '2026-06-30',
            'currency' => 'NGN',
            'royalty_rate' => 70,
            'generate' => true,
        ])->assertStatus(201);

        $line = $response->json('data.lines.0');
        $this->assertEquals('10000.00', $line['revenue_amount']);
        $this->assertEquals('70.00', $line['royalty_rate']);
        $this->assertEquals('7000.00', $line['royalty_amount']);
    }

    // ---------------------------------------------------------------
    // Double-counting guard
    // ---------------------------------------------------------------

    public function test_revenue_already_in_non_void_statement_is_skipped(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));
        $artist = Artist::factory()->create();

        $this->revenueEntry($artist, ['amount' => 10000, 'period_end' => '2026-06-15']);

        // First statement consumes the revenue entry.
        $this->postJson('/api/royalty-statements', [
            'artist_id' => $artist->id,
            'period_start' => '2026-06-01',
            'period_end' => '2026-06-30',
            'currency' => 'NGN',
            'royalty_rate' => 50,
            'generate' => true,
        ])->assertStatus(201);

        // Second statement for same period should find no entries.
        $response = $this->postJson('/api/royalty-statements', [
            'artist_id' => $artist->id,
            'period_start' => '2026-06-01',
            'period_end' => '2026-06-30',
            'currency' => 'NGN',
            'royalty_rate' => 50,
            'generate' => true,
        ])->assertStatus(201);

        $this->assertCount(0, $response->json('data.lines'));
    }

    // ---------------------------------------------------------------
    // Update authorization + status transitions
    // ---------------------------------------------------------------

    public function test_label_manager_can_update_statement(): void
    {
        Sanctum::actingAs($this->userWithRole('label-manager'));
        $statement = RoyaltyStatement::factory()->create();

        $this->patchJson("/api/royalty-statements/{$statement->id}", [
            'notes' => 'Updated notes',
        ])->assertStatus(200)
          ->assertJsonPath('data.notes', 'Updated notes');
    }

    public function test_finance_staff_can_update_statement(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));
        $statement = RoyaltyStatement::factory()->create();

        $this->patchJson("/api/royalty-statements/{$statement->id}", [
            'notes' => 'Finance edit',
        ])->assertStatus(200);
    }

    public function test_ar_cannot_update_statement(): void
    {
        Sanctum::actingAs($this->userWithRole('ar'));
        $statement = RoyaltyStatement::factory()->create();

        $this->patchJson("/api/royalty-statements/{$statement->id}", [
            'notes' => 'Nope',
        ])->assertStatus(403);
    }

    public function test_transition_to_issued_sets_issued_at(): void
    {
        Sanctum::actingAs($this->userWithRole('label-manager'));
        $statement = RoyaltyStatement::factory()->create([
            'status' => RoyaltyStatement::STATUS_DRAFT,
            'issued_at' => null,
        ]);

        $this->patchJson("/api/royalty-statements/{$statement->id}", [
            'status' => RoyaltyStatement::STATUS_ISSUED,
        ])->assertStatus(200)
          ->assertJsonPath('data.status', 'issued');

        $this->assertNotNull($statement->fresh()->issued_at);
    }

    public function test_issued_statement_cannot_revert_to_draft(): void
    {
        Sanctum::actingAs($this->userWithRole('label-manager'));
        $statement = RoyaltyStatement::factory()->create([
            'status' => RoyaltyStatement::STATUS_ISSUED,
            'issued_at' => now()->toDateString(),
        ]);

        $this->patchJson("/api/royalty-statements/{$statement->id}", [
            'status' => RoyaltyStatement::STATUS_DRAFT,
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['status']);
    }

    // ---------------------------------------------------------------
    // Regenerate
    // ---------------------------------------------------------------

    public function test_regenerate_rebuilds_lines_on_draft_statement(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));
        $artist = Artist::factory()->create();

        $this->revenueEntry($artist, ['amount' => 5000, 'period_end' => '2026-06-15']);

        $create = $this->postJson('/api/royalty-statements', [
            'artist_id' => $artist->id,
            'period_start' => '2026-06-01',
            'period_end' => '2026-06-30',
            'currency' => 'NGN',
            'royalty_rate' => 50,
            'generate' => false,
        ])->assertStatus(201);

        $statementId = $create->json('data.id');

        $this->postJson("/api/royalty-statements/{$statementId}/regenerate")
            ->assertStatus(200)
            ->assertJsonCount(1, 'data.lines');
    }

    public function test_regenerate_blocked_on_non_draft_statement(): void
    {
        Sanctum::actingAs($this->userWithRole('label-manager'));
        $statement = RoyaltyStatement::factory()->create([
            'status' => RoyaltyStatement::STATUS_ISSUED,
            'issued_at' => now()->toDateString(),
        ]);

        $this->postJson("/api/royalty-statements/{$statement->id}/regenerate")
            ->assertStatus(422);
    }

    // ---------------------------------------------------------------
    // Delete authorization
    // ---------------------------------------------------------------

    public function test_label_manager_can_soft_delete_statement(): void
    {
        Sanctum::actingAs($this->userWithRole('label-manager'));
        $statement = RoyaltyStatement::factory()->create();

        $this->deleteJson("/api/royalty-statements/{$statement->id}")->assertStatus(200);
        $this->assertSoftDeleted('royalty_statements', ['id' => $statement->id]);
    }

    public function test_finance_staff_cannot_delete_statement(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));
        $statement = RoyaltyStatement::factory()->create();

        $this->deleteJson("/api/royalty-statements/{$statement->id}")->assertStatus(403);
        $this->assertDatabaseHas('royalty_statements', ['id' => $statement->id, 'deleted_at' => null]);
    }

    // ---------------------------------------------------------------
    // Filters
    // ---------------------------------------------------------------

    public function test_search_filter(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));

        RoyaltyStatement::factory()->create(['notes' => 'FINDME-NOTE']);
        RoyaltyStatement::factory()->create(['notes' => 'OTHER-NOTE']);

        $response = $this->getJson('/api/royalty-statements?search=FINDME')->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_status_filter(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));

        RoyaltyStatement::factory()->count(2)->create(['status' => 'draft']);
        RoyaltyStatement::factory()->create(['status' => 'issued']);

        $response = $this->getJson('/api/royalty-statements?status=draft')->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_artist_id_filter(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));

        $artistA = Artist::factory()->create();
        $artistB = Artist::factory()->create();

        RoyaltyStatement::factory()->count(2)->create(['artist_id' => $artistA->id]);
        RoyaltyStatement::factory()->create(['artist_id' => $artistB->id]);

        $response = $this->getJson("/api/royalty-statements?artist_id={$artistA->id}")->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_currency_filter(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));

        RoyaltyStatement::factory()->count(2)->create(['currency' => 'NGN']);
        RoyaltyStatement::factory()->create(['currency' => 'USD']);

        $response = $this->getJson('/api/royalty-statements?currency=ngn')->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_date_range_filter(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));

        RoyaltyStatement::factory()->create(['period_end' => '2026-03-31']);
        RoyaltyStatement::factory()->create(['period_end' => '2026-06-30']);
        RoyaltyStatement::factory()->create(['period_end' => '2026-09-30']);

        $response = $this->getJson('/api/royalty-statements?from=2026-04-01&to=2026-08-31')->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_pagination(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));
        RoyaltyStatement::factory()->count(25)->create();

        $response = $this->getJson('/api/royalty-statements?per_page=10')->assertStatus(200);
        $this->assertCount(10, $response->json('data'));
        $this->assertEquals(25, $response->json('meta.total'));
    }

    // ---------------------------------------------------------------
    // Validation
    // ---------------------------------------------------------------

    public function test_missing_required_fields_returns_422(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));

        $this->postJson('/api/royalty-statements', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['artist_id', 'period_start', 'period_end', 'currency']);
    }

    public function test_invalid_date_order_returns_422(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));
        $artist = Artist::factory()->create();

        $this->postJson('/api/royalty-statements', [
            'artist_id' => $artist->id,
            'period_start' => '2026-06-01',
            'period_end' => '2026-01-01',
            'currency' => 'NGN',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['period_end']);
    }

    public function test_royalty_rate_out_of_range_returns_422(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));
        $artist = Artist::factory()->create();

        $this->postJson('/api/royalty-statements', [
            'artist_id' => $artist->id,
            'period_start' => '2026-01-01',
            'period_end' => '2026-01-31',
            'currency' => 'NGN',
            'royalty_rate' => 150,
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['royalty_rate']);
    }

    public function test_invalid_currency_length_returns_422(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));
        $artist = Artist::factory()->create();

        $this->postJson('/api/royalty-statements', [
            'artist_id' => $artist->id,
            'period_start' => '2026-01-01',
            'period_end' => '2026-01-31',
            'currency' => 'NIGERIA',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['currency']);
    }
}