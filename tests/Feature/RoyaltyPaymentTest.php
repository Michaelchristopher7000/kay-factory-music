<?php

namespace Tests\Feature;

use App\Models\Artist;
use App\Models\Role;
use App\Models\RoyaltyPayment;
use App\Models\RoyaltyStatement;
use App\Models\RoyaltyStatementLine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RoyaltyPaymentTest extends TestCase
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

    /**
     * Create a statement with one line and issued status.
     * Returns [$statement, $artist].
     */
    protected function issuedStatement(float $revenue = 10000, float $rate = 50): array
    {
        $artist = Artist::factory()->create();

        $statement = RoyaltyStatement::factory()->create([
            'artist_id' => $artist->id,
            'status' => RoyaltyStatement::STATUS_ISSUED,
            'issued_at' => now()->toDateString(),
            'currency' => 'NGN',
            'royalty_rate' => $rate,
        ]);

        RoyaltyStatementLine::factory()->create([
            'statement_id' => $statement->id,
            'source' => 'streaming',
            'revenue_amount' => $revenue,
            'royalty_rate' => $rate,
            'royalty_amount' => round($revenue * $rate / 100, 2),
        ]);

        $statement->recomputeTotals();
        $statement->refresh();

        return [$statement, $artist];
    }

    // ---------------------------------------------------------------
    // Auth
    // ---------------------------------------------------------------

    public function test_unauthenticated_request_returns_401(): void
    {
        $this->getJson('/api/royalty-payments')->assertStatus(401);
        $this->postJson('/api/royalty-payments', [])->assertStatus(401);
    }

    // ---------------------------------------------------------------
    // View
    // ---------------------------------------------------------------

    public function test_any_role_can_list_payments(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));
        RoyaltyPayment::factory()->count(3)->create();

        $this->getJson('/api/royalty-payments')
            ->assertStatus(200)
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_show_returns_single_payment(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));
        $payment = RoyaltyPayment::factory()->create();

        $this->getJson("/api/royalty-payments/{$payment->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $payment->id);
    }

    // ---------------------------------------------------------------
    // Create authorization
    // ---------------------------------------------------------------

    public function test_super_admin_can_create_payment(): void
    {
        Sanctum::actingAs($this->userWithRole('super-admin'));
        [$statement] = $this->issuedStatement();

        $this->postJson('/api/royalty-payments', [
            'statement_id' => $statement->id,
            'amount' => 1000,
            'currency' => 'NGN',
            'paid_at' => '2026-06-01',
            'method' => RoyaltyPayment::METHOD_BANK_TRANSFER,
        ])->assertStatus(201);
    }

    public function test_label_manager_can_create_payment(): void
    {
        Sanctum::actingAs($this->userWithRole('label-manager'));
        [$statement] = $this->issuedStatement();

        $this->postJson('/api/royalty-payments', [
            'statement_id' => $statement->id,
            'amount' => 1000,
            'currency' => 'NGN',
            'paid_at' => '2026-06-01',
            'method' => RoyaltyPayment::METHOD_CASH,
        ])->assertStatus(201);
    }

    public function test_finance_staff_can_create_payment(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));
        [$statement] = $this->issuedStatement();

        $this->postJson('/api/royalty-payments', [
            'statement_id' => $statement->id,
            'amount' => 500,
            'currency' => 'NGN',
            'paid_at' => '2026-06-01',
            'method' => RoyaltyPayment::METHOD_BANK_TRANSFER,
        ])->assertStatus(201);
    }

    public function test_ar_cannot_create_payment(): void
    {
        Sanctum::actingAs($this->userWithRole('ar'));
        [$statement] = $this->issuedStatement();

        $this->postJson('/api/royalty-payments', [
            'statement_id' => $statement->id,
            'amount' => 500,
            'currency' => 'NGN',
            'paid_at' => '2026-06-01',
            'method' => RoyaltyPayment::METHOD_BANK_TRANSFER,
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
        [$statement] = $this->issuedStatement();

        $this->postJson('/api/royalty-payments', [
            'statement_id' => $statement->id,
            'amount' => 1000,
            'currency' => 'NGN',
            'paid_at' => '2026-06-01',
            'method' => RoyaltyPayment::METHOD_BANK_TRANSFER,
            'created_by' => $other->id,
        ])->assertStatus(201);

        $payment = RoyaltyPayment::latest('id')->first();
        $this->assertEquals($creator->id, $payment->created_by);
        $this->assertNotEquals($other->id, $payment->created_by);
    }

    public function test_payment_code_auto_generated_and_unique(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));
        [$statement] = $this->issuedStatement();

        $this->postJson('/api/royalty-payments', [
            'statement_id' => $statement->id,
            'amount' => 500,
            'currency' => 'NGN',
            'paid_at' => '2026-06-01',
            'method' => RoyaltyPayment::METHOD_BANK_TRANSFER,
        ])->assertStatus(201);

        $this->postJson('/api/royalty-payments', [
            'statement_id' => $statement->id,
            'amount' => 500,
            'currency' => 'NGN',
            'paid_at' => '2026-06-02',
            'method' => RoyaltyPayment::METHOD_CASH,
        ])->assertStatus(201);

        $codes = RoyaltyPayment::pluck('payment_code')->all();
        $this->assertCount(2, $codes);
        $this->assertCount(2, array_unique($codes));

        foreach ($codes as $code) {
            $this->assertMatchesRegularExpression('/^KFM-PAY-\d{4}$/', $code);
        }
    }

    public function test_artist_id_denormalized_from_statement(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));
        [$statement, $artist] = $this->issuedStatement();

        $otherArtist = Artist::factory()->create();

        $this->postJson('/api/royalty-payments', [
            'statement_id' => $statement->id,
            'artist_id' => $otherArtist->id, // attempt to spoof
            'amount' => 500,
            'currency' => 'NGN',
            'paid_at' => '2026-06-01',
            'method' => RoyaltyPayment::METHOD_BANK_TRANSFER,
        ])->assertStatus(201);

        $payment = RoyaltyPayment::latest('id')->first();
        $this->assertEquals($artist->id, $payment->artist_id);
        $this->assertNotEquals($otherArtist->id, $payment->artist_id);
    }

    // ---------------------------------------------------------------
    // Currency mismatch
    // ---------------------------------------------------------------

    public function test_currency_mismatch_returns_422(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));
        [$statement] = $this->issuedStatement();

        $this->postJson('/api/royalty-payments', [
            'statement_id' => $statement->id,
            'amount' => 500,
            'currency' => 'USD',
            'paid_at' => '2026-06-01',
            'method' => RoyaltyPayment::METHOD_BANK_TRANSFER,
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['currency']);
    }

    // ---------------------------------------------------------------
    // Auto status transitions
    // ---------------------------------------------------------------

    public function test_full_payment_transitions_issued_to_paid(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));
        [$statement] = $this->issuedStatement(10000, 50);

        // total_royalty = 5000

        $this->postJson('/api/royalty-payments', [
            'statement_id' => $statement->id,
            'amount' => 5000,
            'currency' => 'NGN',
            'paid_at' => '2026-06-01',
            'method' => RoyaltyPayment::METHOD_BANK_TRANSFER,
        ])->assertStatus(201);

        $this->assertEquals('paid', $statement->fresh()->status);
    }

    public function test_partial_payment_keeps_status_issued(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));
        [$statement] = $this->issuedStatement(10000, 50);

        $this->postJson('/api/royalty-payments', [
            'statement_id' => $statement->id,
            'amount' => 2500,
            'currency' => 'NGN',
            'paid_at' => '2026-06-01',
            'method' => RoyaltyPayment::METHOD_BANK_TRANSFER,
        ])->assertStatus(201);

        $this->assertEquals('issued', $statement->fresh()->status);
        $this->assertEquals('2500.00', $statement->fresh()->total_paid);
        $this->assertEquals('2500.00', $statement->fresh()->balance);
    }

    public function test_multiple_partial_payments_transition_to_paid(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));
        [$statement] = $this->issuedStatement(10000, 50);

        $this->postJson('/api/royalty-payments', [
            'statement_id' => $statement->id,
            'amount' => 2500,
            'currency' => 'NGN',
            'paid_at' => '2026-06-01',
            'method' => RoyaltyPayment::METHOD_BANK_TRANSFER,
        ])->assertStatus(201);

        $this->postJson('/api/royalty-payments', [
            'statement_id' => $statement->id,
            'amount' => 2500,
            'currency' => 'NGN',
            'paid_at' => '2026-06-15',
            'method' => RoyaltyPayment::METHOD_BANK_TRANSFER,
        ])->assertStatus(201);

        $this->assertEquals('paid', $statement->fresh()->status);
    }

    public function test_delete_payment_reverts_status_from_paid_to_issued(): void
    {
        Sanctum::actingAs($this->userWithRole('label-manager'));
        [$statement] = $this->issuedStatement(10000, 50);

        $response = $this->postJson('/api/royalty-payments', [
            'statement_id' => $statement->id,
            'amount' => 5000,
            'currency' => 'NGN',
            'paid_at' => '2026-06-01',
            'method' => RoyaltyPayment::METHOD_BANK_TRANSFER,
        ])->assertStatus(201);

        $this->assertEquals('paid', $statement->fresh()->status);

        $paymentId = $response->json('data.id');

        $this->deleteJson("/api/royalty-payments/{$paymentId}")->assertStatus(200);

        $this->assertEquals('issued', $statement->fresh()->status);
        $this->assertEquals('0.00', $statement->fresh()->total_paid);
    }

    // ---------------------------------------------------------------
    // Update authorization
    // ---------------------------------------------------------------

    public function test_label_manager_can_update_payment(): void
    {
        Sanctum::actingAs($this->userWithRole('label-manager'));
        [$statement] = $this->issuedStatement();
        $payment = RoyaltyPayment::factory()->create([
            'statement_id' => $statement->id,
            'artist_id' => $statement->artist_id,
            'amount' => 1000,
            'currency' => 'NGN',
        ]);

        $this->patchJson("/api/royalty-payments/{$payment->id}", [
            'notes' => 'Corrected reference',
        ])->assertStatus(200)
          ->assertJsonPath('data.notes', 'Corrected reference');
    }

    public function test_finance_staff_can_update_payment(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));
        [$statement] = $this->issuedStatement();
        $payment = RoyaltyPayment::factory()->create([
            'statement_id' => $statement->id,
            'artist_id' => $statement->artist_id,
            'amount' => 1000,
            'currency' => 'NGN',
        ]);

        $this->patchJson("/api/royalty-payments/{$payment->id}", [
            'amount' => 2000,
        ])->assertStatus(200)
          ->assertJsonPath('data.amount', '2000.00');
    }

    public function test_ar_cannot_update_payment(): void
    {
        Sanctum::actingAs($this->userWithRole('ar'));
        [$statement] = $this->issuedStatement();
        $payment = RoyaltyPayment::factory()->create([
            'statement_id' => $statement->id,
            'artist_id' => $statement->artist_id,
            'currency' => 'NGN',
        ]);

        $this->patchJson("/api/royalty-payments/{$payment->id}", [
            'notes' => 'Nope',
        ])->assertStatus(403);
    }

    public function test_update_cannot_change_statement_id(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));
        [$statement] = $this->issuedStatement();
        [$otherStatement] = $this->issuedStatement();

        $payment = RoyaltyPayment::factory()->create([
            'statement_id' => $statement->id,
            'artist_id' => $statement->artist_id,
            'currency' => 'NGN',
        ]);

        // Send statement_id — must be ignored.
        $this->patchJson("/api/royalty-payments/{$payment->id}", [
            'statement_id' => $otherStatement->id,
            'amount' => 500,
        ])->assertStatus(200);

        $this->assertEquals($statement->id, $payment->fresh()->statement_id);
    }

    // ---------------------------------------------------------------
    // Delete authorization
    // ---------------------------------------------------------------

    public function test_label_manager_can_soft_delete_payment(): void
    {
        Sanctum::actingAs($this->userWithRole('label-manager'));
        [$statement] = $this->issuedStatement();
        $payment = RoyaltyPayment::factory()->create([
            'statement_id' => $statement->id,
            'artist_id' => $statement->artist_id,
            'currency' => 'NGN',
        ]);

        $this->deleteJson("/api/royalty-payments/{$payment->id}")->assertStatus(200);
        $this->assertSoftDeleted('royalty_payments', ['id' => $payment->id]);
    }

    public function test_finance_staff_cannot_delete_payment(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));
        [$statement] = $this->issuedStatement();
        $payment = RoyaltyPayment::factory()->create([
            'statement_id' => $statement->id,
            'artist_id' => $statement->artist_id,
            'currency' => 'NGN',
        ]);

        $this->deleteJson("/api/royalty-payments/{$payment->id}")->assertStatus(403);
    }

    // ---------------------------------------------------------------
    // Filters
    // ---------------------------------------------------------------

    public function test_statement_id_filter(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));

        [$statementA] = $this->issuedStatement();
        [$statementB] = $this->issuedStatement();

        RoyaltyPayment::factory()->count(2)->create([
            'statement_id' => $statementA->id,
            'artist_id' => $statementA->artist_id,
        ]);
        RoyaltyPayment::factory()->create([
            'statement_id' => $statementB->id,
            'artist_id' => $statementB->artist_id,
        ]);

        $response = $this->getJson("/api/royalty-payments?statement_id={$statementA->id}")
            ->assertStatus(200);

        $this->assertCount(2, $response->json('data'));
    }

    public function test_method_filter(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));

        RoyaltyPayment::factory()->count(2)->create(['method' => 'bank_transfer']);
        RoyaltyPayment::factory()->create(['method' => 'cash']);

        $response = $this->getJson('/api/royalty-payments?method=bank_transfer')
            ->assertStatus(200);

        $this->assertCount(2, $response->json('data'));
    }

    public function test_search_filter(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));

        RoyaltyPayment::factory()->create(['reference' => 'FINDME-PAY']);
        RoyaltyPayment::factory()->create(['reference' => 'OTHER-PAY']);

        $response = $this->getJson('/api/royalty-payments?search=FINDME')
            ->assertStatus(200);

        $this->assertCount(1, $response->json('data'));
    }

    public function test_date_range_filter(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));

        RoyaltyPayment::factory()->create(['paid_at' => '2026-03-15']);
        RoyaltyPayment::factory()->create(['paid_at' => '2026-06-15']);
        RoyaltyPayment::factory()->create(['paid_at' => '2026-09-15']);

        $response = $this->getJson('/api/royalty-payments?from=2026-04-01&to=2026-08-31')
            ->assertStatus(200);

        $this->assertCount(1, $response->json('data'));
    }

    public function test_pagination(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));
        RoyaltyPayment::factory()->count(25)->create();

        $response = $this->getJson('/api/royalty-payments?per_page=10')->assertStatus(200);

        $this->assertCount(10, $response->json('data'));
        $this->assertEquals(25, $response->json('meta.total'));
    }

    // ---------------------------------------------------------------
    // Validation
    // ---------------------------------------------------------------

    public function test_missing_required_fields_returns_422(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));

        $this->postJson('/api/royalty-payments', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['statement_id', 'amount', 'currency', 'paid_at', 'method']);
    }

    public function test_zero_amount_returns_422(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));
        [$statement] = $this->issuedStatement();

        $this->postJson('/api/royalty-payments', [
            'statement_id' => $statement->id,
            'amount' => 0,
            'currency' => 'NGN',
            'paid_at' => '2026-06-01',
            'method' => RoyaltyPayment::METHOD_BANK_TRANSFER,
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['amount']);
    }

    public function test_negative_amount_returns_422(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));
        [$statement] = $this->issuedStatement();

        $this->postJson('/api/royalty-payments', [
            'statement_id' => $statement->id,
            'amount' => -100,
            'currency' => 'NGN',
            'paid_at' => '2026-06-01',
            'method' => RoyaltyPayment::METHOD_BANK_TRANSFER,
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['amount']);
    }

    public function test_invalid_method_returns_422(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));
        [$statement] = $this->issuedStatement();

        $this->postJson('/api/royalty-payments', [
            'statement_id' => $statement->id,
            'amount' => 100,
            'currency' => 'NGN',
            'paid_at' => '2026-06-01',
            'method' => 'crypto',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['method']);
    }

    public function test_invalid_statement_id_returns_422(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));

        $this->postJson('/api/royalty-payments', [
            'statement_id' => 99999,
            'amount' => 100,
            'currency' => 'NGN',
            'paid_at' => '2026-06-01',
            'method' => RoyaltyPayment::METHOD_BANK_TRANSFER,
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['statement_id']);
    }
}