<?php

namespace Tests\Feature;

use App\Models\Artist;
use App\Models\RevenueEntry;
use App\Models\Role;
use App\Models\RoyaltyStatement;
use App\Models\RoyaltyStatementLine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RoyaltyStatementLineTest extends TestCase
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

    protected function draftStatementWithLine(): array
    {
        $artist = Artist::factory()->create();

        $statement = RoyaltyStatement::factory()->create([
            'artist_id' => $artist->id,
            'status' => RoyaltyStatement::STATUS_DRAFT,
            'currency' => 'NGN',
            'royalty_rate' => 50,
        ]);

        $line = RoyaltyStatementLine::factory()->create([
            'statement_id' => $statement->id,
            'source' => 'streaming',
            'revenue_amount' => 10000,
            'royalty_rate' => 50,
            'royalty_amount' => 5000,
        ]);

        $statement->recomputeTotals();
        $statement->refresh();

        return [$statement, $line];
    }

    protected function issuedStatementWithLine(): array
    {
        $artist = Artist::factory()->create();

        $statement = RoyaltyStatement::factory()->create([
            'artist_id' => $artist->id,
            'status' => RoyaltyStatement::STATUS_ISSUED,
            'issued_at' => now()->toDateString(),
            'currency' => 'NGN',
            'royalty_rate' => 50,
        ]);

        $line = RoyaltyStatementLine::factory()->create([
            'statement_id' => $statement->id,
            'source' => 'streaming',
            'revenue_amount' => 10000,
            'royalty_rate' => 50,
            'royalty_amount' => 5000,
        ]);

        return [$statement, $line];
    }

    // ---------------------------------------------------------------
    // Auth
    // ---------------------------------------------------------------

    public function test_unauthenticated_request_returns_401(): void
    {
        $this->getJson('/api/royalty-statement-lines')->assertStatus(401);
    }

    // ---------------------------------------------------------------
    // View
    // ---------------------------------------------------------------

    public function test_any_role_can_list_lines(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));
        RoyaltyStatementLine::factory()->count(3)->create();

        $this->getJson('/api/royalty-statement-lines')
            ->assertStatus(200)
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_lines_filtered_by_statement_id(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));

        [$statementA] = $this->draftStatementWithLine();
        [$statementB] = $this->draftStatementWithLine();

        $response = $this->getJson("/api/royalty-statement-lines?statement_id={$statementA->id}")
            ->assertStatus(200);

        $this->assertCount(1, $response->json('data'));
        $this->assertEquals($statementA->id, $response->json('data.0.statement_id'));
    }

    public function test_lines_filtered_by_source(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));

        $statement = RoyaltyStatement::factory()->create(['status' => 'draft']);

        RoyaltyStatementLine::factory()->count(2)->create([
            'statement_id' => $statement->id,
            'source' => 'streaming',
        ]);
        RoyaltyStatementLine::factory()->create([
            'statement_id' => $statement->id,
            'source' => 'sync',
        ]);

        $response = $this->getJson('/api/royalty-statement-lines?source=streaming')->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    // ---------------------------------------------------------------
    // Update on draft
    // ---------------------------------------------------------------

    public function test_label_manager_can_update_line_on_draft(): void
    {
        Sanctum::actingAs($this->userWithRole('label-manager'));
        [, $line] = $this->draftStatementWithLine();

        $this->patchJson("/api/royalty-statement-lines/{$line->id}", [
            'description' => 'Adjusted',
        ])->assertStatus(200)
          ->assertJsonPath('data.description', 'Adjusted');
    }

    public function test_finance_staff_can_update_line_on_draft(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));
        [, $line] = $this->draftStatementWithLine();

        $this->patchJson("/api/royalty-statement-lines/{$line->id}", [
            'description' => 'FS edit',
        ])->assertStatus(200);
    }

    public function test_ar_cannot_update_line(): void
    {
        Sanctum::actingAs($this->userWithRole('ar'));
        [, $line] = $this->draftStatementWithLine();

        $this->patchJson("/api/royalty-statement-lines/{$line->id}", [
            'description' => 'Nope',
        ])->assertStatus(403);
    }

    public function test_change_rate_recomputes_royalty_amount(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));
        [, $line] = $this->draftStatementWithLine();

        $this->patchJson("/api/royalty-statement-lines/{$line->id}", [
            'royalty_rate' => 80,
        ])->assertStatus(200)
          ->assertJsonPath('data.royalty_rate', '80.00')
          ->assertJsonPath('data.royalty_amount', '8000.00');
    }

    public function test_line_update_recomputes_parent_totals(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));
        [$statement, $line] = $this->draftStatementWithLine();

        $this->assertEquals('5000.00', $statement->fresh()->total_royalty);

        $this->patchJson("/api/royalty-statement-lines/{$line->id}", [
            'royalty_rate' => 80,
        ])->assertStatus(200);

        $this->assertEquals('8000.00', $statement->fresh()->total_royalty);
    }

    // ---------------------------------------------------------------
    // Update on non-draft
    // ---------------------------------------------------------------

    public function test_cannot_update_line_when_statement_not_draft(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));
        [, $line] = $this->issuedStatementWithLine();

        $this->patchJson("/api/royalty-statement-lines/{$line->id}", [
            'royalty_rate' => 80,
        ])->assertStatus(403);
    }

    public function test_super_admin_can_update_line_on_issued_statement(): void
    {
        Sanctum::actingAs($this->userWithRole('super-admin'));
        [, $line] = $this->issuedStatementWithLine();

        $this->patchJson("/api/royalty-statement-lines/{$line->id}", [
            'description' => 'SA override',
        ])->assertStatus(200);
    }

    // ---------------------------------------------------------------
    // Delete
    // ---------------------------------------------------------------

    public function test_finance_staff_can_delete_line_on_draft(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));
        [$statement, $line] = $this->draftStatementWithLine();

        $this->deleteJson("/api/royalty-statement-lines/{$line->id}")->assertStatus(200);

        $this->assertSoftDeleted('royalty_statement_lines', ['id' => $line->id]);

        // Parent totals recomputed to zero
        $this->assertEquals('0.00', $statement->fresh()->total_royalty);
    }

    public function test_cannot_delete_line_when_statement_not_draft(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));
        [, $line] = $this->issuedStatementWithLine();

        $this->deleteJson("/api/royalty-statement-lines/{$line->id}")->assertStatus(403);
    }

    // ---------------------------------------------------------------
    // Validation
    // ---------------------------------------------------------------

    public function test_rate_out_of_range_returns_422(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));
        [, $line] = $this->draftStatementWithLine();

        $this->patchJson("/api/royalty-statement-lines/{$line->id}", [
            'royalty_rate' => 150,
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['royalty_rate']);
    }

    public function test_negative_royalty_amount_returns_422(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));
        [, $line] = $this->draftStatementWithLine();

        $this->patchJson("/api/royalty-statement-lines/{$line->id}", [
            'royalty_amount' => -1,
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['royalty_amount']);
    }
}