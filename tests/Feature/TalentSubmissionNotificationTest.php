<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\TalentSubmission;
use App\Models\User;
use App\Notifications\TalentSubmissionReceivedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TalentSubmissionNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);
        Storage::fake('local');
    }

    protected function userWithRole(string $slug): User
    {
        $role = Role::where('slug', $slug)->firstOrFail();

        return User::factory()->create(['role_id' => $role->id]);
    }

    protected function validPayload(): array
    {
        return [
            'full_name' => 'Michael Christopher',
            'email' => 'michael@example.com',
            'phone' => '+2348012345678',
            'location' => 'Lagos, Nigeria',
            'talent_category' => 'Music Artist',
            'consent' => '1',
            'audio' => UploadedFile::fake()->create('demo.mp3', 500, 'audio/mpeg'),
        ];
    }

    public function test_super_admins_and_label_managers_receive_notification(): void
    {
        Notification::fake();

        $superAdmin = $this->userWithRole('super-admin');
        $labelManager = $this->userWithRole('label-manager');
        $otherRole = $this->userWithRole('ar');

        $this->postJson('/api/public/talent-submissions', $this->validPayload())
            ->assertStatus(201);

        Notification::assertSentTo($superAdmin, TalentSubmissionReceivedNotification::class);
        Notification::assertSentTo($labelManager, TalentSubmissionReceivedNotification::class);
        Notification::assertNotSentTo($otherRole, TalentSubmissionReceivedNotification::class);
    }

    public function test_notification_payload_contains_submission_details(): void
    {
        Notification::fake();

        $superAdmin = $this->userWithRole('super-admin');

        $this->postJson('/api/public/talent-submissions', $this->validPayload())
            ->assertStatus(201);

        Notification::assertSentTo(
            $superAdmin,
            TalentSubmissionReceivedNotification::class,
            function ($notification, $channels) use ($superAdmin) {
                $data = $notification->toDatabase($superAdmin);

                return $data['entity_type'] === 'TalentSubmission'
                    && str_starts_with($data['entity_code'], 'KFM-TAL-')
                    && isset($data['action_url'])
                    && str_contains($data['message'], 'Michael Christopher');
            }
        );
    }

    public function test_notification_routes_to_both_channels(): void
    {
        Notification::fake();

        $superAdmin = $this->userWithRole('super-admin');

        $this->postJson('/api/public/talent-submissions', $this->validPayload())
            ->assertStatus(201);

        Notification::assertSentTo(
            $superAdmin,
            TalentSubmissionReceivedNotification::class,
            function ($notification, $channels) {
                return in_array('database', $channels, true)
                    && in_array('mail', $channels, true);
            }
        );
    }

    public function test_submission_still_succeeds_when_notification_fails(): void
    {
        // Force the notification dispatch to throw
        Notification::shouldReceive('send')
            ->once()
            ->andThrow(new \RuntimeException('Simulated mail outage'));

        $superAdmin = $this->userWithRole('super-admin');

        $response = $this->postJson('/api/public/talent-submissions', $this->validPayload());

        $response->assertStatus(201);
        $this->assertDatabaseCount('talent_submissions', 1);
        $this->assertDatabaseCount('users', 1); // super-admin
    }

    public function test_no_notification_when_no_recipients_exist(): void
    {
        Notification::fake();

        // No users seeded with super-admin or label-manager roles
        $this->postJson('/api/public/talent-submissions', $this->validPayload())
            ->assertStatus(201);

        Notification::assertNothingSent();
    }

    public function test_notification_action_url_points_to_admin_detail_page(): void
    {
        Notification::fake();

        $superAdmin = $this->userWithRole('super-admin');

        $this->postJson('/api/public/talent-submissions', $this->validPayload())
            ->assertStatus(201);

        $submission = TalentSubmission::first();

        Notification::assertSentTo(
            $superAdmin,
            TalentSubmissionReceivedNotification::class,
            function ($notification, $channels) use ($superAdmin, $submission) {
                $data = $notification->toDatabase($superAdmin);

                return $data['action_url'] === "/talent-submissions/{$submission->id}";
            }
        );
    }
}