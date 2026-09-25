<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use App\Models\Role;
use App\Models\User;
use App\Notifications\ContactMessageReceivedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ContactMessageTest extends TestCase
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

    protected function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '+2348012345678',
            'subject' => 'Booking enquiry',
            'message' => 'I would like to book an artist for an event next month.',
            'category' => 'booking',
        ], $overrides);
    }

    protected function makeMessage(array $overrides = []): ContactMessage
    {
        return ContactMessage::create(array_merge([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'subject' => 'Test subject',
            'message' => 'Test message content here.',
            'category' => 'general',
            'status' => ContactMessage::STATUS_UNREAD,
        ], $overrides));
    }

    // ---------------------------------------------------------------
    // Public submission
    // ---------------------------------------------------------------

    public function test_public_user_can_submit_message(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/public/contact-messages', $this->validPayload());

        $response->assertStatus(201)
            ->assertJsonStructure(['message', 'reference_number']);

        $this->assertDatabaseCount('contact_messages', 1);

        $msg = ContactMessage::first();
        $this->assertEquals(ContactMessage::STATUS_UNREAD, $msg->status);
        $this->assertMatchesRegularExpression('/^KFM-CON-\d{4}$/', $msg->reference_number);
    }

    public function test_reference_numbers_are_sequential(): void
    {
        Notification::fake();

        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/public/contact-messages', $this->validPayload([
                'email' => "user{$i}@example.com",
            ]))->assertStatus(201);
        }

        $refs = ContactMessage::orderBy('id')->pluck('reference_number')->all();

        $this->assertEquals(['KFM-CON-0001', 'KFM-CON-0002', 'KFM-CON-0003'], $refs);
    }

    public function test_missing_name_returns_422(): void
    {
        $this->postJson('/api/public/contact-messages', $this->validPayload(['name' => '']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_missing_email_returns_422(): void
    {
        $this->postJson('/api/public/contact-messages', $this->validPayload(['email' => '']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_invalid_email_returns_422(): void
    {
        $this->postJson('/api/public/contact-messages', $this->validPayload(['email' => 'not-an-email']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_short_message_returns_422(): void
    {
        $this->postJson('/api/public/contact-messages', $this->validPayload(['message' => 'hi']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['message']);
    }

    public function test_invalid_category_returns_422(): void
    {
        $this->postJson('/api/public/contact-messages', $this->validPayload(['category' => 'spam']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['category']);
    }

    // ---------------------------------------------------------------
    // Notifications
    // ---------------------------------------------------------------

    public function test_super_admins_and_label_managers_receive_notification(): void
    {
        Notification::fake();

        $superAdmin = $this->userWithRole('super-admin');
        $labelManager = $this->userWithRole('label-manager');
        $otherRole = $this->userWithRole('ar');

        $this->postJson('/api/public/contact-messages', $this->validPayload())
            ->assertStatus(201);

        Notification::assertSentTo($superAdmin, ContactMessageReceivedNotification::class);
        Notification::assertSentTo($labelManager, ContactMessageReceivedNotification::class);
        Notification::assertNotSentTo($otherRole, ContactMessageReceivedNotification::class);
    }

    public function test_submission_still_succeeds_when_notification_fails(): void
    {
        Notification::shouldReceive('send')
            ->once()
            ->andThrow(new \RuntimeException('Simulated mail outage'));

        $this->userWithRole('super-admin');

        $this->postJson('/api/public/contact-messages', $this->validPayload())
            ->assertStatus(201);

        $this->assertDatabaseCount('contact_messages', 1);
    }

    // ---------------------------------------------------------------
    // Admin auth
    // ---------------------------------------------------------------

    public function test_unauthenticated_request_returns_401(): void
    {
        $this->getJson('/api/contact-messages')->assertStatus(401);
    }

    public function test_super_admin_can_list_messages(): void
    {
        Sanctum::actingAs($this->userWithRole('super-admin'));
        $this->makeMessage();

        $this->getJson('/api/contact-messages')
            ->assertStatus(200)
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_label_manager_can_list_messages(): void
    {
        Sanctum::actingAs($this->userWithRole('label-manager'));
        $this->makeMessage();

        $this->getJson('/api/contact-messages')->assertStatus(200);
    }

    public function test_other_roles_cannot_list_messages(): void
    {
        foreach (['ar', 'artist-manager', 'finance-staff'] as $slug) {
            Sanctum::actingAs($this->userWithRole($slug));
            $this->getJson('/api/contact-messages')->assertStatus(403);
        }
    }

    // ---------------------------------------------------------------
    // Admin show — auto-read on first view
    // ---------------------------------------------------------------

    public function test_opening_unread_message_marks_it_read(): void
    {
        Sanctum::actingAs($this->userWithRole('super-admin'));
        $msg = $this->makeMessage(['status' => ContactMessage::STATUS_UNREAD]);

        $this->assertNull($msg->read_at);

        $this->getJson("/api/contact-messages/{$msg->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.status', ContactMessage::STATUS_READ);

        $fresh = $msg->fresh();
        $this->assertEquals(ContactMessage::STATUS_READ, $fresh->status);
        $this->assertNotNull($fresh->read_at);
    }

    public function test_opening_read_message_does_not_change_status(): void
    {
        Sanctum::actingAs($this->userWithRole('super-admin'));
        $msg = $this->makeMessage([
            'status' => ContactMessage::STATUS_READ,
            'read_at' => now()->subHour(),
        ]);

        $this->getJson("/api/contact-messages/{$msg->id}")->assertStatus(200);

        $this->assertEquals(ContactMessage::STATUS_READ, $msg->fresh()->status);
    }

    // ---------------------------------------------------------------
    // Admin update
    // ---------------------------------------------------------------

    public function test_status_can_be_updated_and_writes_audit(): void
    {
        Sanctum::actingAs($this->userWithRole('super-admin'));
        $msg = $this->makeMessage();

        $this->patchJson("/api/contact-messages/{$msg->id}", [
            'status' => ContactMessage::STATUS_REPLIED,
        ])->assertStatus(200)
          ->assertJsonPath('data.status', ContactMessage::STATUS_REPLIED);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'contact_message.status_changed',
            'model_type' => 'ContactMessage',
            'model_id' => $msg->id,
        ]);

        $this->assertNotNull($msg->fresh()->replied_at);
    }

    public function test_notes_can_be_updated_and_writes_audit(): void
    {
        Sanctum::actingAs($this->userWithRole('super-admin'));
        $msg = $this->makeMessage();

        $this->patchJson("/api/contact-messages/{$msg->id}", [
            'internal_notes' => 'Follow up next week.',
        ])->assertStatus(200);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'contact_message.notes_updated',
            'model_type' => 'ContactMessage',
            'model_id' => $msg->id,
        ]);
    }

    public function test_assignment_can_be_changed_and_writes_audit(): void
    {
        $admin = $this->userWithRole('super-admin');
        $assignee = $this->userWithRole('label-manager');
        Sanctum::actingAs($admin);

        $msg = $this->makeMessage();

        $this->patchJson("/api/contact-messages/{$msg->id}", [
            'assigned_to' => $assignee->id,
        ])->assertStatus(200)
          ->assertJsonPath('data.assigned_to.id', $assignee->id);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'contact_message.assigned',
            'model_type' => 'ContactMessage',
            'model_id' => $msg->id,
        ]);
    }

    public function test_invalid_status_returns_422(): void
    {
        Sanctum::actingAs($this->userWithRole('super-admin'));
        $msg = $this->makeMessage();

        $this->patchJson("/api/contact-messages/{$msg->id}", [
            'status' => 'bogus',
        ])->assertStatus(422)->assertJsonValidationErrors(['status']);
    }

    public function test_ar_cannot_update_message(): void
    {
        Sanctum::actingAs($this->userWithRole('ar'));
        $msg = $this->makeMessage();

        $this->patchJson("/api/contact-messages/{$msg->id}", [
            'status' => ContactMessage::STATUS_RESOLVED,
        ])->assertStatus(403);
    }

    // ---------------------------------------------------------------
    // Search + filters
    // ---------------------------------------------------------------

    public function test_search_by_name_and_reference(): void
    {
        Sanctum::actingAs($this->userWithRole('super-admin'));

        $this->makeMessage(['name' => 'Find Me']);
        $this->makeMessage(['name' => 'Other', 'email' => 'other@example.com']);

        $response = $this->getJson('/api/contact-messages?search=Find')
            ->assertStatus(200);

        $this->assertCount(1, $response->json('data'));
    }

    public function test_status_filter(): void
    {
        Sanctum::actingAs($this->userWithRole('super-admin'));

        $this->makeMessage(['status' => ContactMessage::STATUS_UNREAD]);
        $this->makeMessage(['status' => ContactMessage::STATUS_UNREAD, 'email' => 'x@example.com']);
        $this->makeMessage(['status' => ContactMessage::STATUS_RESOLVED, 'email' => 'y@example.com']);

        $response = $this->getJson('/api/contact-messages?status=unread')
            ->assertStatus(200);

        $this->assertCount(2, $response->json('data'));
    }

    public function test_category_filter(): void
    {
        Sanctum::actingAs($this->userWithRole('super-admin'));

        $this->makeMessage(['category' => 'booking']);
        $this->makeMessage(['category' => 'booking', 'email' => 'b2@example.com']);
        $this->makeMessage(['category' => 'press', 'email' => 'p1@example.com']);

        $response = $this->getJson('/api/contact-messages?category=booking')
            ->assertStatus(200);

        $this->assertCount(2, $response->json('data'));
    }

    public function test_pagination(): void
    {
        Sanctum::actingAs($this->userWithRole('super-admin'));

        for ($i = 0; $i < 25; $i++) {
            $this->makeMessage(['email' => "user{$i}@example.com"]);
        }

        $response = $this->getJson('/api/contact-messages?per_page=10')
            ->assertStatus(200);

        $this->assertCount(10, $response->json('data'));
        $this->assertEquals(25, $response->json('meta.total'));
    }
}