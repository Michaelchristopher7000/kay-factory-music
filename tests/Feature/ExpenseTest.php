<?php

namespace Tests\Feature;

use App\Models\Artist;
use App\Models\Distribution;
use App\Models\Expense;
use App\Models\Release;
use App\Models\Role;
use App\Models\Track;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ExpenseTest extends TestCase
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
        $this->getJson('/api/expenses')->assertStatus(401);
        $this->postJson('/api/expenses', [])->assertStatus(401);
    }

    // ---------------------------------------------------------------
    // View
    // ---------------------------------------------------------------

    public function test_any_authenticated_role_can_list_expenses(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));
        Expense::factory()->count(3)->create();

        $this->getJson('/api/expenses')
            ->assertStatus(200)
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_show_returns_single_expense(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));
        $expense = Expense::factory()->create();

        $this->getJson("/api/expenses/{$expense->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $expense->id);
    }

    // ---------------------------------------------------------------
    // Create authorization
    // ---------------------------------------------------------------

    public function test_super_admin_can_create_expense(): void
    {
        Sanctum::actingAs($this->userWithRole('super-admin'));

        $this->postJson('/api/expenses', [
            'category' => 'studio',
            'amount' => 50000,
            'currency' => 'NGN',
            'incurred_at' => '2026-01-15',
        ])->assertStatus(201);
    }

    public function test_label_manager_can_create_expense(): void
    {
        Sanctum::actingAs($this->userWithRole('label-manager'));

        $this->postJson('/api/expenses', [
            'category' => 'marketing',
            'amount' => 250000,
            'currency' => 'NGN',
            'incurred_at' => '2026-02-20',
        ])->assertStatus(201);
    }

    public function test_finance_staff_can_create_expense(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));

        $this->postJson('/api/expenses', [
            'category' => 'distribution_fee',
            'amount' => 75000,
            'currency' => 'NGN',
            'incurred_at' => '2026-03-10',
        ])->assertStatus(201);
    }

    public function test_ar_cannot_create_expense(): void
    {
        Sanctum::actingAs($this->userWithRole('ar'));

        $this->postJson('/api/expenses', [
            'category' => 'studio',
            'amount' => 100,
            'currency' => 'NGN',
        ])->assertStatus(403);
    }

    public function test_artist_manager_cannot_create_expense(): void
    {
        Sanctum::actingAs($this->userWithRole('artist-manager'));

        $this->postJson('/api/expenses', [
            'category' => 'studio',
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

        $this->postJson('/api/expenses', [
            'category' => 'studio',
            'amount' => 5000,
            'currency' => 'NGN',
            'created_by' => $other->id,
        ])->assertStatus(201);

        $expense = Expense::latest('id')->first();
        $this->assertEquals($creator->id, $expense->created_by);
        $this->assertNotEquals($other->id, $expense->created_by);
    }

    public function test_expense_code_is_auto_generated_and_unique(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));

        $this->postJson('/api/expenses', [
            'category' => 'studio',
            'amount' => 100,
            'currency' => 'NGN',
        ])->assertStatus(201);

        $this->postJson('/api/expenses', [
            'category' => 'travel',
            'amount' => 200,
            'currency' => 'USD',
        ])->assertStatus(201);

        $codes = Expense::pluck('expense_code')->all();

        $this->assertCount(2, $codes);
        $this->assertCount(2, array_unique($codes));

        foreach ($codes as $code) {
            $this->assertMatchesRegularExpression('/^KFM-EXP-\d{4}$/', $code);
        }
    }

    // ---------------------------------------------------------------
    // Optional attributions + chain
    // ---------------------------------------------------------------

    public function test_expense_can_be_created_without_attribution(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));

        $response = $this->postJson('/api/expenses', [
            'category' => 'admin',
            'amount' => 300,
            'currency' => 'NGN',
        ])->assertStatus(201);

        $response
            ->assertJsonPath('data.artist_id', null)
            ->assertJsonPath('data.release_id', null)
            ->assertJsonPath('data.track_id', null)
            ->assertJsonPath('data.distribution_id', null);
    }

    public function test_expense_can_link_to_full_chain(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));

        $artist = Artist::factory()->create();
        $release = Release::factory()->create(['artist_id' => $artist->id]);
        $track = Track::factory()->create(['artist_id' => $artist->id]);
        $distribution = Distribution::factory()->create(['release_id' => $release->id]);

        $this->postJson('/api/expenses', [
            'category' => 'distribution_fee',
            'artist_id' => $artist->id,
            'release_id' => $release->id,
            'track_id' => $track->id,
            'distribution_id' => $distribution->id,
            'amount' => 5000,
            'currency' => 'NGN',
        ])->assertStatus(201);

        $expense = Expense::latest('id')->first();
        $this->assertEquals($artist->id, $expense->artist_id);
        $this->assertEquals($release->id, $expense->release_id);
        $this->assertEquals($track->id, $expense->track_id);
        $this->assertEquals($distribution->id, $expense->distribution_id);
    }

    // ---------------------------------------------------------------
    // Update authorization
    // ---------------------------------------------------------------

    public function test_label_manager_can_update_expense(): void
    {
        Sanctum::actingAs($this->userWithRole('label-manager'));
        $expense = Expense::factory()->create();

        $this->putJson("/api/expenses/{$expense->id}", ['amount' => 12345])
            ->assertStatus(200)
            ->assertJsonPath('data.amount', '12345.00');
    }

    public function test_finance_staff_can_update_expense(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));
        $expense = Expense::factory()->create();

        $this->patchJson("/api/expenses/{$expense->id}", ['notes' => 'updated'])
            ->assertStatus(200);
    }

    public function test_ar_cannot_update_expense(): void
    {
        Sanctum::actingAs($this->userWithRole('ar'));
        $expense = Expense::factory()->create();

        $this->patchJson("/api/expenses/{$expense->id}", ['amount' => 1])
            ->assertStatus(403);
    }

    // ---------------------------------------------------------------
    // Delete authorization + soft delete
    // ---------------------------------------------------------------

    public function test_label_manager_can_soft_delete_expense(): void
    {
        Sanctum::actingAs($this->userWithRole('label-manager'));
        $expense = Expense::factory()->create();

        $this->deleteJson("/api/expenses/{$expense->id}")->assertStatus(200);

        $this->assertSoftDeleted('expenses', ['id' => $expense->id]);
    }

    public function test_finance_staff_cannot_delete_expense(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));
        $expense = Expense::factory()->create();

        $this->deleteJson("/api/expenses/{$expense->id}")->assertStatus(403);
        $this->assertDatabaseHas('expenses', ['id' => $expense->id, 'deleted_at' => null]);
    }

    // ---------------------------------------------------------------
    // FK survives soft delete of linked entities
    // ---------------------------------------------------------------

    public function test_expense_survives_artist_soft_delete(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));

        $artist = Artist::factory()->create();
        $expense = Expense::factory()->create(['artist_id' => $artist->id]);

        $artist->delete();

        $this->assertDatabaseHas('expenses', ['id' => $expense->id, 'deleted_at' => null]);

        $this->getJson("/api/expenses/{$expense->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $expense->id);
    }

    public function test_expense_survives_distribution_soft_delete(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));

        $distribution = Distribution::factory()->create();
        $expense = Expense::factory()->create(['distribution_id' => $distribution->id]);

        $distribution->delete();

        $this->assertDatabaseHas('expenses', ['id' => $expense->id, 'deleted_at' => null]);
    }

    // ---------------------------------------------------------------
    // Filters
    // ---------------------------------------------------------------

    public function test_search_filter(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));

        Expense::factory()->create(['reference' => 'FINDME-INV']);
        Expense::factory()->create(['reference' => 'OTHER-INV']);

        $response = $this->getJson('/api/expenses?search=FINDME')->assertStatus(200);

        $this->assertCount(1, $response->json('data'));
    }

    public function test_category_filter(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));

        Expense::factory()->count(2)->create(['category' => 'studio']);
        Expense::factory()->create(['category' => 'travel']);

        $response = $this->getJson('/api/expenses?category=studio')->assertStatus(200);

        $this->assertCount(2, $response->json('data'));
    }

    public function test_currency_filter(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));

        Expense::factory()->count(2)->create(['currency' => 'NGN']);
        Expense::factory()->create(['currency' => 'USD']);

        $response = $this->getJson('/api/expenses?currency=ngn')->assertStatus(200);

        $this->assertCount(2, $response->json('data'));
    }

    public function test_date_range_filter(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));

        Expense::factory()->create(['incurred_at' => '2026-03-15']);
        Expense::factory()->create(['incurred_at' => '2026-06-15']);
        Expense::factory()->create(['incurred_at' => '2026-09-15']);

        $response = $this->getJson('/api/expenses?from=2026-04-01&to=2026-08-31')
            ->assertStatus(200);

        $this->assertCount(1, $response->json('data'));
    }

    public function test_release_id_filter(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));

        $release = Release::factory()->create();
        Expense::factory()->count(2)->create(['release_id' => $release->id]);
        Expense::factory()->create(['release_id' => null]);

        $response = $this->getJson("/api/expenses?release_id={$release->id}")
            ->assertStatus(200);

        $this->assertCount(2, $response->json('data'));
    }

    public function test_pagination(): void
    {
        Sanctum::actingAs($this->userWithRole('general-staff'));
        Expense::factory()->count(25)->create();

        $response = $this->getJson('/api/expenses?per_page=10')->assertStatus(200);

        $this->assertCount(10, $response->json('data'));
        $this->assertEquals(25, $response->json('meta.total'));
    }

    // ---------------------------------------------------------------
    // Validation
    // ---------------------------------------------------------------

    public function test_missing_required_fields_returns_422(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));

        $this->postJson('/api/expenses', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['category', 'amount', 'currency']);
    }

    public function test_invalid_category_returns_422(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));

        $this->postJson('/api/expenses', [
            'category' => 'bogus',
            'amount' => 100,
            'currency' => 'NGN',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['category']);
    }

    public function test_zero_amount_returns_422(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));

        $this->postJson('/api/expenses', [
            'category' => 'studio',
            'amount' => 0,
            'currency' => 'NGN',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['amount']);
    }

    public function test_negative_amount_returns_422(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));

        $this->postJson('/api/expenses', [
            'category' => 'studio',
            'amount' => -100,
            'currency' => 'NGN',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['amount']);
    }

    public function test_invalid_currency_length_returns_422(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));

        $this->postJson('/api/expenses', [
            'category' => 'studio',
            'amount' => 100,
            'currency' => 'NIGERIA',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['currency']);
    }

    public function test_invalid_fk_returns_422(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));

        $this->postJson('/api/expenses', [
            'category' => 'studio',
            'amount' => 100,
            'currency' => 'NGN',
            'artist_id' => 99999,
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['artist_id']);
    }

    public function test_paid_at_before_incurred_at_returns_422(): void
    {
        Sanctum::actingAs($this->userWithRole('finance-staff'));

        $this->postJson('/api/expenses', [
            'category' => 'studio',
            'amount' => 100,
            'currency' => 'NGN',
            'incurred_at' => '2026-06-01',
            'paid_at' => '2026-01-01',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['paid_at']);
    }
}