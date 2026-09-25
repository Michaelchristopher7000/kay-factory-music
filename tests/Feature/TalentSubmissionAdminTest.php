<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\TalentSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TalentSubmissionAdminTest extends TestCase
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

    protected function makeSubmission(array $overrides = []): TalentSubmission
    {
        $folder = 'talent-submissions/' . uniqid();

        return TalentSubmission::create(array_merge([
            'full_name' => 'Michael Christopher',
            'email' => 'michael@example.com',
            'phone' => '+2348012345678',
            'location' => 'Lagos, Nigeria',
            'talent_category' => 'Music Artist',
            'bio' => 'Afrobeats artist from Lagos.',
            'message' => 'Looking forward to working with KFM.',
            'social_links' => ['instagram' => 'https://instagram.com/mc'],
            'audio_path' => "{$folder}/audio/demo.mp3",
            'status' => TalentSubmission::STATUS_PENDING,
        ], $overrides));
    }

    // ---------------------------------------------------------------
    // Auth
    // ---------------------------------------------------------------

    public function test_unauthenticated_request_returns_401(): void
    {
        $this->getJson('/api/talent-submissions')->assertStatus(401);

        // POST to the admin endpoint is not defined (public submissions
        // live at /api/public/talent-submissions), so Laravel returns 405.
        // The 401 check only applies to routes that exist.
        $this->getJson('/api/talent-submissions/1')->assertStatus(401);
    }

    // ---------------------------------------------------------------
    // Index authorization
    // ---------------------------------------------------------------

    public function test_super_admin_can_list_submissions(): void
    {
        Sanctum::actingAs($this->userWithRole('super-admin'));
        $this->makeSubmission();

        $this->getJson('/api/talent-submissions')
            ->assertStatus(200)
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_label_manager_can_list_submissions(): void
    {
        Sanctum::actingAs($this->userWithRole('label-manager'));
        $this->makeSubmission();

        $this->getJson('/api/talent-submissions')->assertStatus(200);
    }

    public function test_other_roles_cannot_list_submissions(): void
    {
        foreach (['ar', 'artist-manager', 'finance-staff', 'general-staff'] as $slug) {
            Sanctum::actingAs($this->userWithRole($slug));

            $this->getJson('/api/talent-submissions')
                ->assertStatus(403);
        }
    }

    // ---------------------------------------------------------------
    // Show
    // ---------------------------------------------------------------

    public function test_super_admin_can_show_submission(): void
    {
        Sanctum::actingAs($this->userWithRole('super-admin'));
        $submission = $this->makeSubmission();

        $this->getJson("/api/talent-submissions/{$submission->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $submission->id)
            ->assertJsonPath('data.reference_number', $submission->reference_number);
    }

    public function test_show_does_not_leak_internal_paths(): void
    {
        Sanctum::actingAs($this->userWithRole('super-admin'));
        $submission = $this->makeSubmission();

        $response = $this->getJson("/api/talent-submissions/{$submission->id}")
            ->assertStatus(200);

        $body = $response->getContent();

        $this->assertStringNotContainsString('audio_path', $body);
        $this->assertStringNotContainsString('video_path', $body);
        $this->assertStringNotContainsString('image_path', $body);
    }

    public function test_show_exposes_authenticated_media_urls(): void
    {
        Sanctum::actingAs($this->userWithRole('super-admin'));
        $submission = $this->makeSubmission([
            'video_path' => 'talent-submissions/x/video/clip.mp4',
            'image_path' => 'talent-submissions/x/image/photo.jpg',
        ]);

        $response = $this->getJson("/api/talent-submissions/{$submission->id}")
            ->assertStatus(200);

        $this->assertNotNull($response->json('data.audio_url'));
        $this->assertNotNull($response->json('data.video_url'));
        $this->assertNotNull($response->json('data.image_url'));

        $this->assertStringContainsString('/api/talent-submissions/', $response->json('data.audio_url'));
        $this->assertStringContainsString('/media/audio', $response->json('data.audio_url'));
    }

    public function test_not_found_returns_404(): void
    {
        Sanctum::actingAs($this->userWithRole('super-admin'));

        $this->getJson('/api/talent-submissions/99999')->assertStatus(404);
    }

    // ---------------------------------------------------------------
    // Update authorization
    // ---------------------------------------------------------------

    public function test_super_admin_can_update_submission(): void
    {
        Sanctum::actingAs($this->userWithRole('super-admin'));
        $submission = $this->makeSubmission();

        $this->patchJson("/api/talent-submissions/{$submission->id}", [
            'status' => TalentSubmission::STATUS_REVIEWING,
        ])->assertStatus(200)
          ->assertJsonPath('data.status', 'reviewing');
    }

    public function test_label_manager_can_update_submission(): void
    {
        Sanctum::actingAs($this->userWithRole('label-manager'));
        $submission = $this->makeSubmission();

        $this->patchJson("/api/talent-submissions/{$submission->id}", [
            'status' => TalentSubmission::STATUS_SHORTLISTED,
        ])->assertStatus(200)
          ->assertJsonPath('data.status', 'shortlisted');
    }

    public function test_ar_cannot_update_submission(): void
    {
        Sanctum::actingAs($this->userWithRole('ar'));
        $submission = $this->makeSubmission();

        $this->patchJson("/api/talent-submissions/{$submission->id}", [
            'status' => TalentSubmission::STATUS_ACCEPTED,
        ])->assertStatus(403);
    }

    // ---------------------------------------------------------------
    // Status transitions
    // ---------------------------------------------------------------

    public function test_status_change_writes_audit_entry(): void
    {
        Sanctum::actingAs($this->userWithRole('super-admin'));
        $submission = $this->makeSubmission();

        $this->patchJson("/api/talent-submissions/{$submission->id}", [
            'status' => TalentSubmission::STATUS_REVIEWING,
        ])->assertStatus(200);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'talent_submission.status_changed',
            'model_type' => 'TalentSubmission',
            'model_id' => $submission->id,
        ]);
    }

    public function test_status_change_sets_reviewed_at_on_first_review(): void
    {
        Sanctum::actingAs($this->userWithRole('super-admin'));
        $submission = $this->makeSubmission();

        $this->assertNull($submission->reviewed_at);

        $this->patchJson("/api/talent-submissions/{$submission->id}", [
            'status' => TalentSubmission::STATUS_REVIEWING,
        ])->assertStatus(200);

        $this->assertNotNull($submission->fresh()->reviewed_at);
    }

    public function test_invalid_status_returns_422(): void
    {
        Sanctum::actingAs($this->userWithRole('super-admin'));
        $submission = $this->makeSubmission();

        $this->patchJson("/api/talent-submissions/{$submission->id}", [
            'status' => 'not-a-status',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['status']);
    }

    // ---------------------------------------------------------------
    // Notes
    // ---------------------------------------------------------------

    public function test_manager_notes_can_be_set(): void
    {
        Sanctum::actingAs($this->userWithRole('label-manager'));
        $submission = $this->makeSubmission();

        $this->patchJson("/api/talent-submissions/{$submission->id}", [
            'manager_notes' => 'Promising talent — schedule a call.',
        ])->assertStatus(200);

        $this->assertEquals('Promising talent — schedule a call.', $submission->fresh()->manager_notes);
    }

    public function test_manager_notes_change_writes_audit_entry(): void
    {
        Sanctum::actingAs($this->userWithRole('super-admin'));
        $submission = $this->makeSubmission();

        $this->patchJson("/api/talent-submissions/{$submission->id}", [
            'manager_notes' => 'First note.',
        ])->assertStatus(200);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'talent_submission.notes_updated',
            'model_type' => 'TalentSubmission',
            'model_id' => $submission->id,
        ]);
    }

    public function test_notes_are_not_exposed_on_public_submission_endpoint(): void
    {
        // Sanity: the public POST response should never carry manager_notes
        $response = $this->postJson('/api/public/talent-submissions', [
            'full_name' => 'Public User',
            'email' => 'public@example.com',
            'phone' => '+2348000000000',
            'talent_category' => 'Singer',
            'consent' => '1',
            'audio' => UploadedFile::fake()->create('demo.mp3', 500, 'audio/mpeg'),
        ]);

        $response->assertStatus(201);
        $this->assertStringNotContainsString('manager_notes', $response->getContent());
    }

    // ---------------------------------------------------------------
    // Reviewer
    // ---------------------------------------------------------------

    public function test_reviewer_can_be_assigned(): void
    {
        $admin = $this->userWithRole('super-admin');
        $reviewer = $this->userWithRole('label-manager');
        Sanctum::actingAs($admin);

        $submission = $this->makeSubmission();

        $this->patchJson("/api/talent-submissions/{$submission->id}", [
            'reviewed_by' => $reviewer->id,
        ])->assertStatus(200)
          ->assertJsonPath('data.reviewed_by.id', $reviewer->id);

        $this->assertEquals($reviewer->id, $submission->fresh()->reviewed_by);
    }

    public function test_reviewer_change_writes_audit_entry(): void
    {
        $admin = $this->userWithRole('super-admin');
        $reviewer = $this->userWithRole('label-manager');
        Sanctum::actingAs($admin);

        $submission = $this->makeSubmission();

        $this->patchJson("/api/talent-submissions/{$submission->id}", [
            'reviewed_by' => $reviewer->id,
        ])->assertStatus(200);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'talent_submission.reviewer_changed',
            'model_type' => 'TalentSubmission',
            'model_id' => $submission->id,
        ]);
    }

    public function test_invalid_reviewer_id_returns_422(): void
    {
        Sanctum::actingAs($this->userWithRole('super-admin'));
        $submission = $this->makeSubmission();

        $this->patchJson("/api/talent-submissions/{$submission->id}", [
            'reviewed_by' => 99999,
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['reviewed_by']);
    }

    // ---------------------------------------------------------------
    // Media streaming
    // ---------------------------------------------------------------

    public function test_media_endpoint_requires_auth(): void
    {
        $submission = $this->makeSubmission();

        $this->getJson("/api/talent-submissions/{$submission->id}/media/audio")
            ->assertStatus(401);
    }

    public function test_media_endpoint_streams_audio_for_authorized_staff(): void
    {
        Sanctum::actingAs($this->userWithRole('super-admin'));

        $submission = $this->makeSubmission();
        Storage::disk('local')->put($submission->audio_path, 'fake audio');

        $this->get("/api/talent-submissions/{$submission->id}/media/audio")
            ->assertStatus(200);
    }

    public function test_media_endpoint_returns_404_when_missing(): void
    {
        Sanctum::actingAs($this->userWithRole('super-admin'));

        $submission = $this->makeSubmission([
            'audio_path' => null,
            'video_path' => null,
            'image_path' => null,
        ]);

        $this->getJson("/api/talent-submissions/{$submission->id}/media/audio")
            ->assertStatus(404);
    }

    public function test_invalid_media_type_returns_404(): void
    {
        Sanctum::actingAs($this->userWithRole('super-admin'));
        $submission = $this->makeSubmission();

        $this->getJson("/api/talent-submissions/{$submission->id}/media/exe")
            ->assertStatus(404);
    }

    // ---------------------------------------------------------------
    // Search + filters
    // ---------------------------------------------------------------

    public function test_search_by_name(): void
    {
        Sanctum::actingAs($this->userWithRole('super-admin'));

        $this->makeSubmission(['full_name' => 'Find Me']);
        $this->makeSubmission(['full_name' => 'Other Person', 'email' => 'other@example.com']);

        $response = $this->getJson('/api/talent-submissions?search=Find')
            ->assertStatus(200);

        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Find Me', $response->json('data.0.full_name'));
    }

    public function test_search_by_reference(): void
    {
        Sanctum::actingAs($this->userWithRole('super-admin'));

        $target = $this->makeSubmission();
        $this->makeSubmission(['email' => 'other@example.com']);

        $response = $this->getJson("/api/talent-submissions?search={$target->reference_number}")
            ->assertStatus(200);

        $this->assertCount(1, $response->json('data'));
    }

    public function test_status_filter(): void
    {
        Sanctum::actingAs($this->userWithRole('super-admin'));

        $this->makeSubmission(['status' => TalentSubmission::STATUS_PENDING]);
        $this->makeSubmission(['status' => TalentSubmission::STATUS_PENDING, 'email' => 'p2@example.com']);
        $this->makeSubmission(['status' => TalentSubmission::STATUS_ACCEPTED, 'email' => 'a1@example.com']);

        $response = $this->getJson('/api/talent-submissions?status=pending')
            ->assertStatus(200);

        $this->assertCount(2, $response->json('data'));
    }

    public function test_pagination(): void
    {
        Sanctum::actingAs($this->userWithRole('super-admin'));

        for ($i = 0; $i < 25; $i++) {
            $this->makeSubmission(['email' => "user{$i}@example.com"]);
        }

        $response = $this->getJson('/api/talent-submissions?per_page=10')
            ->assertStatus(200);

        $this->assertCount(10, $response->json('data'));
        $this->assertEquals(25, $response->json('meta.total'));
    }
}
