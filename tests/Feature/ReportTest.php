<?php

namespace Tests\Feature;

use App\Models\Artist;
use App\Models\Distribution;
use App\Models\Expense;
use App\Models\Release;
use App\Models\RevenueEntry;
use App\Models\Role;
use App\Models\RoyaltyPayment;
use App\Models\RoyaltyStatement;
use App\Models\RoyaltyStatementLine;
use App\Models\Track;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReportTest extends TestCase
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
    // Authentication
    // ===============================================================

    public function test_all_report_endpoints_require_auth(): void
    {
        $this->getJson('/api/reports/artists/summary')->assertStatus(401);
        $this->getJson('/api/reports/releases/performance')->assertStatus(401);
        $this->getJson('/api/reports/distributions/status')->assertStatus(401);
        $this->getJson('/api/reports/revenue/by-source')->assertStatus(401);
        $this->getJson('/api/reports/expenses/by-category')->assertStatus(401);
        $this->getJson('/api/reports/royalties/balances')->assertStatus(401);
    }

    // ===============================================================
    // RBAC — financial reports restricted
    // ===============================================================

    public function test_financial_reports_denied_for_unprivileged_roles(): void
    {
        $unprivileged = ['ar', 'artist-manager', 'marketing-staff', 'distribution-manager', 'general-staff'];

        foreach ($unprivileged as $slug) {
            Sanctum::actingAs($this->userWithRole($slug));

            $this->getJson('/api/reports/artists/summary')->assertStatus(403);
            $this->getJson('/api/reports/revenue/by-source')->assertStatus(403);
            $this->getJson('/api/reports/expenses/by-category')->assertStatus(403);
            $this->getJson('/api/reports/royalties/balances')->assertStatus(403);
        }
    }

    public function test_financial_reports_allowed_for_finance_roles(): void
    {
        foreach (['super-admin', 'label-manager', 'finance-staff'] as $slug) {
            Sanctum::actingAs($this->userWithRole($slug));

            $this->getJson('/api/reports/artists/summary')->assertStatus(200);
            $this->getJson('/api/reports/revenue/by-source')->assertStatus(200);
            $this->getJson('/api/reports/expenses/by-category')->assertStatus(200);
            $this->getJson('/api/reports/royalties/balances')->assertStatus(200);
        }
    }

    public function test_non_financial_reports_allowed_for_any_role(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));

        $this->getJson('/api/reports/releases/performance')->assertStatus(200);
        $this->getJson('/api/reports/distributions/status')->assertStatus(200);
    }

    // ===============================================================
    // Response shape
    // ===============================================================

    public function test_response_shape_has_data_and_meta(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));

        $this->getJson('/api/reports/artists/summary')
            ->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'meta' => ['generated_at', 'filters'],
            ]);
    }

    public function test_filters_are_reflected_in_meta(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));

        $response = $this->getJson('/api/reports/revenue/by-source?from=2026-01-01&to=2026-12-31&currency=ngn')
            ->assertStatus(200);

        $this->assertEquals('2026-01-01', $response->json('meta.filters.from'));
        $this->assertEquals('2026-12-31', $response->json('meta.filters.to'));
        $this->assertEquals('NGN', $response->json('meta.filters.currency'));
    }

    // ===============================================================
    // 1. Artist Summary
    // ===============================================================

    public function test_artist_summary_calculates_totals(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));

        $artist = Artist::factory()->create(['name' => 'Summary Test']);

        Track::factory()->count(3)->create(['artist_id' => $artist->id]);
        Release::factory()->count(2)->create(['artist_id' => $artist->id]);

        RevenueEntry::factory()->create([
            'artist_id' => $artist->id,
            'amount' => 1000,
            'currency' => 'NGN',
            'period_end' => '2026-06-01',
        ]);
        RevenueEntry::factory()->create([
            'artist_id' => $artist->id,
            'amount' => 500,
            'currency' => 'NGN',
            'period_end' => '2026-06-15',
        ]);

        Expense::factory()->create([
            'artist_id' => $artist->id,
            'amount' => 200,
            'currency' => 'NGN',
            'incurred_at' => '2026-06-01',
        ]);

        $statement = RoyaltyStatement::factory()->create([
            'artist_id' => $artist->id,
            'status' => RoyaltyStatement::STATUS_ISSUED,
            'currency' => 'NGN',
            'total_royalty' => 700,
            'total_paid' => 300,
            'balance' => 400,
        ]);

        RoyaltyPayment::factory()->create([
            'statement_id' => $statement->id,
            'artist_id' => $artist->id,
            'amount' => 300,
            'currency' => 'NGN',
        ]);

        $response = $this->getJson("/api/reports/artists/summary?artist_id={$artist->id}")
            ->assertStatus(200);

        $row = $response->json('data.0');
        $this->assertEquals($artist->id, $row['artist_id']);
        $this->assertEquals('Summary Test', $row['artist_name']);
        $this->assertEquals(3, $row['total_tracks']);
        $this->assertEquals(2, $row['total_releases']);
        $this->assertEquals('1500.00', $row['total_revenue']);
        $this->assertEquals('200.00', $row['total_expenses']);
        $this->assertEquals('700.00', $row['total_royalty_generated']);
        $this->assertEquals('300.00', $row['total_royalty_paid']);
        $this->assertEquals('400.00', $row['outstanding_royalty_balance']);
    }

    public function test_artist_summary_excludes_void_statements(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));

        $artist = Artist::factory()->create();

        RoyaltyStatement::factory()->create([
            'artist_id' => $artist->id,
            'status' => RoyaltyStatement::STATUS_VOID,
            'currency' => 'NGN',
            'total_royalty' => 9999,
            'total_paid' => 0,
            'balance' => 9999,
        ]);

        RoyaltyStatement::factory()->create([
            'artist_id' => $artist->id,
            'status' => RoyaltyStatement::STATUS_ISSUED,
            'currency' => 'NGN',
            'total_royalty' => 1000,
            'total_paid' => 0,
            'balance' => 1000,
        ]);

        $response = $this->getJson("/api/reports/artists/summary?artist_id={$artist->id}")
            ->assertStatus(200);

        $this->assertEquals('1000.00', $response->json('data.0.total_royalty_generated'));
    }

    // ===============================================================
    // 2. Release Performance
    // ===============================================================

    public function test_release_performance_calculates_net(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));

        $artist = Artist::factory()->create();
        $release = Release::factory()->create([
            'artist_id' => $artist->id,
            'release_date' => '2026-06-01',
        ]);

        RevenueEntry::factory()->create([
            'release_id' => $release->id,
            'artist_id' => $artist->id,
            'amount' => 5000,
            'currency' => 'NGN',
            'period_end' => '2026-06-30',
        ]);

        Expense::factory()->create([
            'release_id' => $release->id,
            'artist_id' => $artist->id,
            'amount' => 2000,
            'currency' => 'NGN',
            'incurred_at' => '2026-06-15',
        ]);

        $response = $this->getJson("/api/reports/releases/performance")
            ->assertStatus(200);

        $row = collect($response->json('data'))->firstWhere('release_id', $release->id);
        $this->assertNotNull($row);
        $this->assertEquals('5000.00', $row['revenue']);
        $this->assertEquals('2000.00', $row['expenses']);
        $this->assertEquals('3000.00', $row['net_amount']);
    }

    public function test_release_performance_date_filter(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));

        $artist = Artist::factory()->create();
        $inRange = Release::factory()->create([
            'artist_id' => $artist->id,
            'release_date' => '2026-06-15',
        ]);
        $outOfRange = Release::factory()->create([
            'artist_id' => $artist->id,
            'release_date' => '2025-01-15',
        ]);

        $response = $this->getJson('/api/reports/releases/performance?from=2026-06-01&to=2026-06-30')
            ->assertStatus(200);

        $ids = collect($response->json('data'))->pluck('release_id')->all();
        $this->assertContains($inRange->id, $ids);
        $this->assertNotContains($outOfRange->id, $ids);
    }

    // ===============================================================
    // 3. Distribution Status
    // ===============================================================
    public function test_distribution_status_groups_by_platform(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));

        // Two different releases for two spotify rows (unique constraint is release+platform).
        $release1 = Release::factory()->create();
        $release2 = Release::factory()->create();
        $release3 = Release::factory()->create();

        Distribution::factory()->create(['release_id' => $release1->id, 'platform' => 'spotify', 'status' => 'live']);
        Distribution::factory()->create(['release_id' => $release2->id, 'platform' => 'spotify', 'status' => 'pending']);
        Distribution::factory()->create(['release_id' => $release3->id, 'platform' => 'apple_music', 'status' => 'live']);

        $response = $this->getJson('/api/reports/distributions/status')->assertStatus(200);

        $spotify = collect($response->json('data'))->firstWhere('platform', 'spotify');
        $this->assertEquals(2, $spotify['total_distributions']);
        $this->assertEquals(1, $spotify['live_count']);
        $this->assertEquals(1, $spotify['pending_count']);
        $this->assertEquals(0, $spotify['submitted_count']);

        $apple = collect($response->json('data'))->firstWhere('platform', 'apple_music');
        $this->assertEquals(1, $apple['total_distributions']);
        $this->assertEquals(1, $apple['live_count']);
    }

    // ===============================================================
    // 4. Revenue Breakdown
    // ===============================================================

    public function test_revenue_breakdown_groups_by_source_and_currency(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));

        RevenueEntry::factory()->create(['source' => 'streaming', 'currency' => 'NGN', 'amount' => 1000, 'period_end' => '2026-06-15']);
        RevenueEntry::factory()->create(['source' => 'streaming', 'currency' => 'NGN', 'amount' => 500, 'period_end' => '2026-06-20']);
        RevenueEntry::factory()->create(['source' => 'streaming', 'currency' => 'USD', 'amount' => 300, 'period_end' => '2026-06-15']);
        RevenueEntry::factory()->create(['source' => 'sync', 'currency' => 'NGN', 'amount' => 2000, 'period_end' => '2026-06-15']);

        $response = $this->getJson('/api/reports/revenue/by-source')->assertStatus(200);

        $streamingNgn = collect($response->json('data'))->first(
            fn ($r) => $r['source'] === 'streaming' && $r['currency'] === 'NGN'
        );
        $this->assertNotNull($streamingNgn);
        $this->assertEquals('1500.00', $streamingNgn['total_amount']);
        $this->assertEquals(2, $streamingNgn['entry_count']);

        $streamingUsd = collect($response->json('data'))->first(
            fn ($r) => $r['source'] === 'streaming' && $r['currency'] === 'USD'
        );
        $this->assertNotNull($streamingUsd);
        $this->assertEquals('300.00', $streamingUsd['total_amount']);
    }

    public function test_revenue_breakdown_date_filter(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));

        RevenueEntry::factory()->create(['source' => 'streaming', 'currency' => 'NGN', 'amount' => 1000, 'period_end' => '2026-03-15']);
        RevenueEntry::factory()->create(['source' => 'streaming', 'currency' => 'NGN', 'amount' => 2000, 'period_end' => '2026-06-15']);
        RevenueEntry::factory()->create(['source' => 'streaming', 'currency' => 'NGN', 'amount' => 4000, 'period_end' => '2026-09-15']);

        $response = $this->getJson('/api/reports/revenue/by-source?from=2026-04-01&to=2026-08-31')
            ->assertStatus(200);

        $row = $response->json('data.0');
        $this->assertEquals('2000.00', $row['total_amount']);
        $this->assertEquals(1, $row['entry_count']);
    }

    public function test_revenue_breakdown_currency_filter(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));

        RevenueEntry::factory()->create(['source' => 'streaming', 'currency' => 'NGN', 'amount' => 1000, 'period_end' => '2026-06-15']);
        RevenueEntry::factory()->create(['source' => 'streaming', 'currency' => 'USD', 'amount' => 300, 'period_end' => '2026-06-15']);

        $response = $this->getJson('/api/reports/revenue/by-source?currency=ngn')->assertStatus(200);

        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('NGN', $response->json('data.0.currency'));
    }

    // ===============================================================
    // 5. Expense Breakdown
    // ===============================================================

    public function test_expense_breakdown_groups_by_category(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));

        Expense::factory()->create(['category' => 'studio', 'currency' => 'NGN', 'amount' => 5000, 'incurred_at' => '2026-06-15']);
        Expense::factory()->create(['category' => 'studio', 'currency' => 'NGN', 'amount' => 3000, 'incurred_at' => '2026-06-20']);
        Expense::factory()->create(['category' => 'marketing', 'currency' => 'NGN', 'amount' => 2000, 'incurred_at' => '2026-06-15']);

        $response = $this->getJson('/api/reports/expenses/by-category')->assertStatus(200);

        $studio = collect($response->json('data'))->firstWhere('category', 'studio');
        $this->assertEquals('8000.00', $studio['total_amount']);
        $this->assertEquals(2, $studio['entry_count']);
    }

    public function test_expense_breakdown_date_filter(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));

        Expense::factory()->create(['category' => 'studio', 'currency' => 'NGN', 'amount' => 1000, 'incurred_at' => '2026-03-15']);
        Expense::factory()->create(['category' => 'studio', 'currency' => 'NGN', 'amount' => 2000, 'incurred_at' => '2026-06-15']);

        $response = $this->getJson('/api/reports/expenses/by-category?from=2026-04-01&to=2026-08-31')
            ->assertStatus(200);

        $row = $response->json('data.0');
        $this->assertEquals('2000.00', $row['total_amount']);
    }

    // ===============================================================
    // 6. Royalty Balances
    // ===============================================================

    public function test_royalty_balances_calculates_per_artist(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));

        $artist = Artist::factory()->create(['name' => 'Royalty Test']);

        RoyaltyStatement::factory()->create([
            'artist_id' => $artist->id,
            'status' => RoyaltyStatement::STATUS_ISSUED,
            'currency' => 'NGN',
            'total_royalty' => 5000,
            'total_paid' => 2000,
            'balance' => 3000,
        ]);

        RoyaltyStatement::factory()->create([
            'artist_id' => $artist->id,
            'status' => RoyaltyStatement::STATUS_PAID,
            'currency' => 'NGN',
            'total_royalty' => 1500,
            'total_paid' => 1500,
            'balance' => 0,
        ]);

        $response = $this->getJson('/api/reports/royalties/balances')->assertStatus(200);

        $row = collect($response->json('data'))->firstWhere('artist_id', $artist->id);
        $this->assertNotNull($row);
        $this->assertEquals('6500.00', $row['total_royalty']);
        $this->assertEquals('3500.00', $row['total_paid']);
        $this->assertEquals('3000.00', $row['outstanding_balance']);
    }

    public function test_royalty_balances_excludes_void_statements(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));

        $artist = Artist::factory()->create();

        RoyaltyStatement::factory()->create([
            'artist_id' => $artist->id,
            'status' => RoyaltyStatement::STATUS_VOID,
            'currency' => 'NGN',
            'total_royalty' => 9999,
            'total_paid' => 0,
            'balance' => 9999,
        ]);

        RoyaltyStatement::factory()->create([
            'artist_id' => $artist->id,
            'status' => RoyaltyStatement::STATUS_ISSUED,
            'currency' => 'NGN',
            'total_royalty' => 1000,
            'total_paid' => 0,
            'balance' => 1000,
        ]);

        $response = $this->getJson('/api/reports/royalties/balances')->assertStatus(200);

        $row = collect($response->json('data'))->firstWhere('artist_id', $artist->id);
        $this->assertEquals('1000.00', $row['total_royalty']);
    }

    public function test_royalty_balances_separates_currencies(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));

        $artist = Artist::factory()->create();

        RoyaltyStatement::factory()->create([
            'artist_id' => $artist->id,
            'status' => RoyaltyStatement::STATUS_ISSUED,
            'currency' => 'NGN',
            'total_royalty' => 1000,
            'total_paid' => 0,
            'balance' => 1000,
        ]);

        RoyaltyStatement::factory()->create([
            'artist_id' => $artist->id,
            'status' => RoyaltyStatement::STATUS_ISSUED,
            'currency' => 'USD',
            'total_royalty' => 500,
            'total_paid' => 0,
            'balance' => 500,
        ]);

        $response = $this->getJson('/api/reports/royalties/balances')->assertStatus(200);

        $rows = collect($response->json('data'))->where('artist_id', $artist->id);
        $this->assertCount(2, $rows);

        $ngn = $rows->firstWhere('currency', 'NGN');
        $usd = $rows->firstWhere('currency', 'USD');
        $this->assertEquals('1000.00', $ngn['total_royalty']);
        $this->assertEquals('500.00', $usd['total_royalty']);
    }

    public function test_royalty_balances_currency_filter(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));

        $artist = Artist::factory()->create();

        RoyaltyStatement::factory()->create([
            'artist_id' => $artist->id,
            'status' => RoyaltyStatement::STATUS_ISSUED,
            'currency' => 'NGN',
            'total_royalty' => 1000,
            'total_paid' => 0,
            'balance' => 1000,
        ]);

        RoyaltyStatement::factory()->create([
            'artist_id' => $artist->id,
            'status' => RoyaltyStatement::STATUS_ISSUED,
            'currency' => 'USD',
            'total_royalty' => 500,
            'total_paid' => 0,
            'balance' => 500,
        ]);

        $response = $this->getJson('/api/reports/royalties/balances?currency=usd')->assertStatus(200);

        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('USD', $response->json('data.0.currency'));
    }
}