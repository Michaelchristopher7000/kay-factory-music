<?php

namespace Tests\Feature;

use App\Models\Artist;
use App\Models\Contract;
use App\Models\Distribution;
use App\Models\Release;
use App\Models\Role;
use App\Models\RoyaltyPayment;
use App\Models\RoyaltyStatement;
use App\Models\User;
use App\Notifications\ContractCreatedNotification;
use App\Notifications\DistributionStatusChangedNotification;
use App\Notifications\ReleaseCreatedNotification;
use App\Notifications\ReleaseStatusChangedNotification;
use App\Notifications\RoyaltyPaymentRecordedNotification;
use App\Notifications\RoyaltyStatementIssuedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationTriggerTest extends TestCase
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

    // =============================================================
    // Contract created
    // =============================================================

    public function test_contract_created_sends_notification(): void
    {
        Notification::fake();

        $superAdmin = $this->userWithRole('super-admin');
        $labelManager = $this->userWithRole('label-manager');
        $ar = $this->userWithRole('ar');

        $artist = Artist::factory()->create();

        Sanctum::actingAs($labelManager);

        Contract::factory()->create(['artist_id' => $artist->id]);

        Notification::assertSentTo($superAdmin, ContractCreatedNotification::class);
        Notification::assertSentTo($ar, ContractCreatedNotification::class);
        Notification::assertNotSentTo($labelManager, ContractCreatedNotification::class);
    }

    public function test_unrelated_roles_do_not_get_contract_notification(): void
    {
        Notification::fake();

        $financeStaff = $this->userWithRole('finance-staff');
        $generalStaff = $this->userWithRole('general-staff');

        $artist = Artist::factory()->create();
        $labelManager = $this->userWithRole('label-manager');

        Sanctum::actingAs($labelManager);
        Contract::factory()->create(['artist_id' => $artist->id]);

        Notification::assertNotSentTo($financeStaff, ContractCreatedNotification::class);
        Notification::assertNotSentTo($generalStaff, ContractCreatedNotification::class);
    }

    // =============================================================
    // Release created + status change
    // =============================================================

    public function test_release_created_sends_notification(): void
    {
        Notification::fake();

        $superAdmin = $this->userWithRole('super-admin');
        $distributionManager = $this->userWithRole('distribution-manager');

        $artist = Artist::factory()->create();
        $labelManager = $this->userWithRole('label-manager');

        Sanctum::actingAs($labelManager);
        Release::factory()->create(['artist_id' => $artist->id]);

        Notification::assertSentTo($superAdmin, ReleaseCreatedNotification::class);
        Notification::assertSentTo($distributionManager, ReleaseCreatedNotification::class);
        Notification::assertNotSentTo($labelManager, ReleaseCreatedNotification::class);
    }

    public function test_release_status_change_sends_notification(): void
    {
        Notification::fake();

        $superAdmin = $this->userWithRole('super-admin');
        $artist = Artist::factory()->create();
        $labelManager = $this->userWithRole('label-manager');

        $release = Release::factory()->create([
            'artist_id' => $artist->id,
            'status' => Release::STATUS_DRAFT,
        ]);

        Sanctum::actingAs($labelManager);

        $release->update(['status' => Release::STATUS_RELEASED]);

        Notification::assertSentTo($superAdmin, ReleaseStatusChangedNotification::class);
        Notification::assertNotSentTo($labelManager, ReleaseStatusChangedNotification::class);
    }

    public function test_release_update_without_status_change_does_not_notify(): void
    {
        Notification::fake();

        $artist = Artist::factory()->create();
        $release = Release::factory()->create(['artist_id' => $artist->id]);
        $labelManager = $this->userWithRole('label-manager');

        Sanctum::actingAs($labelManager);

        $release->update(['title' => 'Renamed Title']);

        Notification::assertNothingSent();
    }

    // =============================================================
    // Distribution status
    // =============================================================

    public function test_distribution_status_change_sends_notification(): void
    {
        Notification::fake();

        $superAdmin = $this->userWithRole('super-admin');
        $distributionManager = $this->userWithRole('distribution-manager');
        $release = Release::factory()->create();

        $distribution = Distribution::factory()->create([
            'release_id' => $release->id,
            'status' => 'pending',
        ]);

        $labelManager = $this->userWithRole('label-manager');
        Sanctum::actingAs($labelManager);

        $distribution->update(['status' => 'submitted']);

        Notification::assertSentTo($superAdmin, DistributionStatusChangedNotification::class);
        Notification::assertSentTo($distributionManager, DistributionStatusChangedNotification::class);
        Notification::assertNotSentTo($labelManager, DistributionStatusChangedNotification::class);
    }

    // =============================================================
    // Royalty statement issued
    // =============================================================

    public function test_royalty_statement_issued_sends_notification(): void
    {
        Notification::fake();

        $superAdmin = $this->userWithRole('super-admin');
        $financeStaff = $this->userWithRole('finance-staff');
        $labelManager = $this->userWithRole('label-manager');

        $artist = Artist::factory()->create();
        $statement = RoyaltyStatement::factory()->create([
            'artist_id' => $artist->id,
            'status' => RoyaltyStatement::STATUS_DRAFT,
        ]);

        Sanctum::actingAs($labelManager);

        $statement->update([
            'status' => RoyaltyStatement::STATUS_ISSUED,
            'issued_at' => now()->toDateString(),
        ]);

        Notification::assertSentTo($superAdmin, RoyaltyStatementIssuedNotification::class);
        Notification::assertSentTo($financeStaff, RoyaltyStatementIssuedNotification::class);
        Notification::assertNotSentTo($labelManager, RoyaltyStatementIssuedNotification::class);
    }

    // =============================================================
    // Royalty payment
    // =============================================================

    public function test_royalty_payment_sends_notification(): void
    {
        Notification::fake();

        $superAdmin = $this->userWithRole('super-admin');
        $financeStaff = $this->userWithRole('finance-staff');
        $labelManager = $this->userWithRole('label-manager');

        $artist = Artist::factory()->create();
        $statement = RoyaltyStatement::factory()->create([
            'artist_id' => $artist->id,
            'status' => RoyaltyStatement::STATUS_ISSUED,
            'issued_at' => now()->toDateString(),
            'currency' => 'NGN',
        ]);

        Sanctum::actingAs($labelManager);

        RoyaltyPayment::factory()->create([
            'statement_id' => $statement->id,
            'artist_id' => $artist->id,
            'currency' => 'NGN',
        ]);

        Notification::assertSentTo($superAdmin, RoyaltyPaymentRecordedNotification::class);
        Notification::assertSentTo($financeStaff, RoyaltyPaymentRecordedNotification::class);
        Notification::assertNotSentTo($labelManager, RoyaltyPaymentRecordedNotification::class);
    }
}