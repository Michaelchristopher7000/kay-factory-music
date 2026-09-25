<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\StaffConversation;
use App\Models\StaffMessage;
use App\Models\User;
use App\Notifications\StaffMessageReceivedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StaffMessageTest extends TestCase
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
    // Auth
    // ---------------------------------------------------------------

    public function test_unauthenticated_request_returns_401(): void
    {
        $this->getJson('/api/staff-messages/conversations')->assertStatus(401);
        $this->getJson('/api/staff-messages/recipients')->assertStatus(401);
        $this->getJson('/api/staff-messages/unread-count')->assertStatus(401);
    }

    // ---------------------------------------------------------------
    // Recipients
    // ---------------------------------------------------------------

    public function test_user_sees_all_other_staff_in_recipients(): void
    {
        $me = $this->userWithRole('ar');
        $other1 = $this->userWithRole('label-manager');
        $other2 = $this->userWithRole('super-admin');

        Sanctum::actingAs($me);

        $response = $this->getJson('/api/staff-messages/recipients')->assertStatus(200);

        $ids = collect($response->json('data'))->pluck('id')->all();

        $this->assertContains($other1->id, $ids);
        $this->assertContains($other2->id, $ids);
        $this->assertNotContains($me->id, $ids);
    }

    // ---------------------------------------------------------------
    // Start conversation
    // ---------------------------------------------------------------

    public function test_user_can_start_conversation_with_first_message(): void
    {
        Notification::fake();

        $me = $this->userWithRole('ar');
        $other = $this->userWithRole('label-manager');
        Sanctum::actingAs($me);

        $response = $this->postJson('/api/staff-messages/conversations', [
            'user_id' => $other->id,
            'body' => 'Hey, quick question about a release.',
        ])->assertStatus(201);

        $response->assertJsonStructure(['data' => ['conversation_id', 'message']]);

        $this->assertDatabaseCount('staff_conversations', 1);
        $this->assertDatabaseCount('staff_messages', 1);
        $this->assertDatabaseHas('staff_messages', [
            'sender_id' => $me->id,
            'body' => 'Hey, quick question about a release.',
        ]);
    }

    public function test_starting_conversation_with_same_user_twice_reuses_it(): void
    {
        Notification::fake();

        $me = $this->userWithRole('ar');
        $other = $this->userWithRole('label-manager');
        Sanctum::actingAs($me);

        $r1 = $this->postJson('/api/staff-messages/conversations', [
            'user_id' => $other->id,
            'body' => 'First message.',
        ])->assertStatus(201);

        $r2 = $this->postJson('/api/staff-messages/conversations', [
            'user_id' => $other->id,
            'body' => 'Second message.',
        ])->assertStatus(201);

        $this->assertEquals($r1->json('data.conversation_id'), $r2->json('data.conversation_id'));
        $this->assertDatabaseCount('staff_conversations', 1);
        $this->assertDatabaseCount('staff_messages', 2);
    }

    public function test_user_cannot_start_conversation_with_self(): void
    {
        $me = $this->userWithRole('ar');
        Sanctum::actingAs($me);

        $this->postJson('/api/staff-messages/conversations', [
            'user_id' => $me->id,
            'body' => 'Hi me.',
        ])->assertStatus(422);
    }

    public function test_user_cannot_start_conversation_with_nonexistent_user(): void
    {
        $me = $this->userWithRole('ar');
        Sanctum::actingAs($me);

        $this->postJson('/api/staff-messages/conversations', [
            'user_id' => 99999,
            'body' => 'Hello?',
        ])->assertStatus(422);
    }

    public function test_start_conversation_requires_body(): void
    {
        $me = $this->userWithRole('ar');
        $other = $this->userWithRole('label-manager');
        Sanctum::actingAs($me);

        $this->postJson('/api/staff-messages/conversations', [
            'user_id' => $other->id,
        ])->assertStatus(422)->assertJsonValidationErrors(['body']);
    }

    // ---------------------------------------------------------------
    // Send in existing conversation
    // ---------------------------------------------------------------

    public function test_user_can_send_message_in_existing_conversation(): void
    {
        Notification::fake();

        $a = $this->userWithRole('ar');
        $b = $this->userWithRole('label-manager');
        $conv = StaffConversation::findOrCreateBetween($a->id, $b->id);

        Sanctum::actingAs($a);

        $this->postJson("/api/staff-messages/conversations/{$conv->id}/messages", [
            'body' => 'Follow-up question.',
        ])->assertStatus(201);

        $this->assertDatabaseHas('staff_messages', [
            'conversation_id' => $conv->id,
            'sender_id' => $a->id,
            'body' => 'Follow-up question.',
        ]);
    }

    public function test_non_participant_cannot_send_in_conversation(): void
    {
        $a = $this->userWithRole('ar');
        $b = $this->userWithRole('label-manager');
        $outsider = $this->userWithRole('finance-staff');

        $conv = StaffConversation::findOrCreateBetween($a->id, $b->id);

        Sanctum::actingAs($outsider);

        $this->postJson("/api/staff-messages/conversations/{$conv->id}/messages", [
            'body' => 'Sneaking in.',
        ])->assertStatus(403);
    }

    // ---------------------------------------------------------------
    // List conversations
    // ---------------------------------------------------------------

    public function test_user_only_sees_their_own_conversations(): void
    {
        $a = $this->userWithRole('ar');
        $b = $this->userWithRole('label-manager');
        $c = $this->userWithRole('finance-staff');

        StaffConversation::findOrCreateBetween($a->id, $b->id);
        StaffConversation::findOrCreateBetween($b->id, $c->id);

        Sanctum::actingAs($a);

        $response = $this->getJson('/api/staff-messages/conversations')->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    // ---------------------------------------------------------------
    // Show conversation + auto-mark read
    // ---------------------------------------------------------------

    public function test_opening_conversation_marks_incoming_as_read(): void
    {
        $a = $this->userWithRole('ar');
        $b = $this->userWithRole('label-manager');
        $conv = StaffConversation::findOrCreateBetween($a->id, $b->id);

        // B sends to A
        StaffMessage::create([
            'conversation_id' => $conv->id,
            'sender_id' => $b->id,
            'body' => 'Hi A.',
        ]);

        $this->assertDatabaseHas('staff_messages', ['read_at' => null]);

        Sanctum::actingAs($a);
        $this->getJson("/api/staff-messages/conversations/{$conv->id}")->assertStatus(200);

        $this->assertDatabaseMissing('staff_messages', [
            'conversation_id' => $conv->id,
            'sender_id' => $b->id,
            'read_at' => null,
        ]);
    }

    public function test_non_participant_cannot_show_conversation(): void
    {
        $a = $this->userWithRole('ar');
        $b = $this->userWithRole('label-manager');
        $outsider = $this->userWithRole('finance-staff');

        $conv = StaffConversation::findOrCreateBetween($a->id, $b->id);
        Sanctum::actingAs($outsider);

        $this->getJson("/api/staff-messages/conversations/{$conv->id}")->assertStatus(403);
    }

    // ---------------------------------------------------------------
    // Unread count
    // ---------------------------------------------------------------

    public function test_unread_count_reflects_incoming_messages(): void
    {
        $a = $this->userWithRole('ar');
        $b = $this->userWithRole('label-manager');
        $conv = StaffConversation::findOrCreateBetween($a->id, $b->id);

        StaffMessage::create([
            'conversation_id' => $conv->id,
            'sender_id' => $b->id,
            'body' => 'Message 1',
        ]);
        StaffMessage::create([
            'conversation_id' => $conv->id,
            'sender_id' => $b->id,
            'body' => 'Message 2',
        ]);

        Sanctum::actingAs($a);

        $this->getJson('/api/staff-messages/unread-count')
            ->assertStatus(200)
            ->assertJsonPath('data.unread_count', 2);
    }

    public function test_own_messages_do_not_count_as_unread(): void
    {
        $a = $this->userWithRole('ar');
        $b = $this->userWithRole('label-manager');
        $conv = StaffConversation::findOrCreateBetween($a->id, $b->id);

        StaffMessage::create([
            'conversation_id' => $conv->id,
            'sender_id' => $a->id,
            'body' => 'Mine',
        ]);

        Sanctum::actingAs($a);

        $this->getJson('/api/staff-messages/unread-count')
            ->assertStatus(200)
            ->assertJsonPath('data.unread_count', 0);
    }

    // ---------------------------------------------------------------
    // Notifications
    // ---------------------------------------------------------------

    public function test_recipient_gets_notification_when_message_sent(): void
    {
        Notification::fake();

        $a = $this->userWithRole('ar');
        $b = $this->userWithRole('label-manager');
        $conv = StaffConversation::findOrCreateBetween($a->id, $b->id);

        Sanctum::actingAs($a);

        $this->postJson("/api/staff-messages/conversations/{$conv->id}/messages", [
            'body' => 'Ping.',
        ])->assertStatus(201);

        Notification::assertSentTo($b, StaffMessageReceivedNotification::class);
        Notification::assertNotSentTo($a, StaffMessageReceivedNotification::class);
    }

    public function test_message_still_sends_when_notification_fails(): void
    {
        Notification::shouldReceive('send')
            ->once()
            ->andThrow(new \RuntimeException('Simulated outage'));

        $a = $this->userWithRole('ar');
        $b = $this->userWithRole('label-manager');
        $conv = StaffConversation::findOrCreateBetween($a->id, $b->id);

        Sanctum::actingAs($a);

        $this->postJson("/api/staff-messages/conversations/{$conv->id}/messages", [
            'body' => 'Should still save.',
        ])->assertStatus(201);

        $this->assertDatabaseHas('staff_messages', ['body' => 'Should still save.']);
    }

    public function test_notification_payload_has_conversation_link(): void
    {
        Notification::fake();

        $a = $this->userWithRole('ar');
        $b = $this->userWithRole('label-manager');
        $conv = StaffConversation::findOrCreateBetween($a->id, $b->id);

        Sanctum::actingAs($a);

        $this->postJson("/api/staff-messages/conversations/{$conv->id}/messages", [
            'body' => 'Ping.',
        ])->assertStatus(201);

        Notification::assertSentTo(
            $b,
            StaffMessageReceivedNotification::class,
            function ($notification, $channels) use ($b, $conv) {
                $data = $notification->toDatabase($b);
                return in_array('database', $channels, true)
                    && ! in_array('mail', $channels, true)
                    && $data['action_url'] === "/messages/{$conv->id}"
                    && $data['entity_type'] === 'StaffMessage';
            }
        );
    }
}