<?php

namespace Tests\Feature;

use App\Models\TalentSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TalentSubmissionTest extends TestCase
{
    use RefreshDatabase;

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    protected function validPayload(array $overrides = []): array
    {
        return array_merge([
            'full_name' => 'Michael Christopher',
            'email' => 'michael@example.com',
            'phone' => '+2348012345678',
            'location' => 'Lagos, Nigeria',
            'talent_category' => 'Music Artist',
            'bio' => 'Afrobeats artist from Lagos.',
            'message' => 'Looking forward to working with KFM.',
            'consent' => '1',
        ], $overrides);
    }

    protected function fakeAudio(): UploadedFile
    {
        // 1 KB fake MP3 — passes mime check via extension hinting
        return UploadedFile::fake()->create('demo.mp3', 500, 'audio/mpeg');
    }

    protected function fakeVideo(): UploadedFile
    {
        return UploadedFile::fake()->create('clip.mp4', 500, 'video/mp4');
    }

    protected function fakeImage(): UploadedFile
    {
        return UploadedFile::fake()->image('photo.jpg', 300, 300);
    }

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    // ---------------------------------------------------------------
    // Success paths
    // ---------------------------------------------------------------

    public function test_public_user_can_submit_with_audio_only(): void
    {
        $response = $this->postJson('/api/public/talent-submissions', $this->validPayload([
            'audio' => $this->fakeAudio(),
        ]));

        $response->assertStatus(201)
            ->assertJsonStructure(['message', 'reference_number']);

        $this->assertDatabaseCount('talent_submissions', 1);

        $submission = TalentSubmission::first();
        $this->assertNotNull($submission->audio_path);
        $this->assertNull($submission->video_path);
        $this->assertNull($submission->image_path);

        Storage::disk('local')->assertExists($submission->audio_path);
    }

    public function test_public_user_can_submit_with_video_only(): void
    {
        $this->postJson('/api/public/talent-submissions', $this->validPayload([
            'video' => $this->fakeVideo(),
        ]))->assertStatus(201);

        $submission = TalentSubmission::first();
        $this->assertNotNull($submission->video_path);
        Storage::disk('local')->assertExists($submission->video_path);
    }

    public function test_public_user_can_submit_with_image_only(): void
    {
        $this->postJson('/api/public/talent-submissions', $this->validPayload([
            'image' => $this->fakeImage(),
        ]))->assertStatus(201);

        $submission = TalentSubmission::first();
        $this->assertNotNull($submission->image_path);
        Storage::disk('local')->assertExists($submission->image_path);
    }

    public function test_public_user_can_submit_with_all_three_media_types(): void
    {
        $this->postJson('/api/public/talent-submissions', $this->validPayload([
            'audio' => $this->fakeAudio(),
            'video' => $this->fakeVideo(),
            'image' => $this->fakeImage(),
        ]))->assertStatus(201);

        $submission = TalentSubmission::first();
        $this->assertNotNull($submission->audio_path);
        $this->assertNotNull($submission->video_path);
        $this->assertNotNull($submission->image_path);
    }

    // ---------------------------------------------------------------
    // Defaults + reference format
    // ---------------------------------------------------------------

    public function test_status_defaults_to_pending(): void
    {
        $this->postJson('/api/public/talent-submissions', $this->validPayload([
            'audio' => $this->fakeAudio(),
        ]))->assertStatus(201);

        $this->assertEquals(TalentSubmission::STATUS_PENDING, TalentSubmission::first()->status);
    }

    public function test_reference_number_is_generated_and_follows_format(): void
    {
        $this->postJson('/api/public/talent-submissions', $this->validPayload([
            'audio' => $this->fakeAudio(),
        ]))->assertStatus(201);

        $reference = TalentSubmission::first()->reference_number;

        $this->assertMatchesRegularExpression('/^KFM-TAL-\d{4}$/', $reference);
    }

    public function test_reference_numbers_are_sequential_and_unique(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/public/talent-submissions', $this->validPayload([
                'email' => "user{$i}@example.com",
                'audio' => $this->fakeAudio(),
            ]))->assertStatus(201);
        }

        $references = TalentSubmission::pluck('reference_number')->all();

        $this->assertCount(3, $references);
        $this->assertCount(3, array_unique($references));
        $this->assertEquals(['KFM-TAL-0001', 'KFM-TAL-0002', 'KFM-TAL-0003'], $references);
    }

    // ---------------------------------------------------------------
    // Media requirement
    // ---------------------------------------------------------------

    public function test_submission_without_any_media_is_rejected(): void
    {
        $this->postJson('/api/public/talent-submissions', $this->validPayload())
            ->assertStatus(422)
            ->assertJsonValidationErrors(['audio']);

        $this->assertDatabaseCount('talent_submissions', 0);
    }

    // ---------------------------------------------------------------
    // Required field validation
    // ---------------------------------------------------------------

    public function test_missing_full_name_returns_422(): void
    {
        $this->postJson('/api/public/talent-submissions', $this->validPayload([
            'full_name' => '',
            'audio' => $this->fakeAudio(),
        ]))->assertStatus(422)
            ->assertJsonValidationErrors(['full_name']);
    }

    public function test_missing_email_returns_422(): void
    {
        $this->postJson('/api/public/talent-submissions', $this->validPayload([
            'email' => '',
            'audio' => $this->fakeAudio(),
        ]))->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_invalid_email_returns_422(): void
    {
        $this->postJson('/api/public/talent-submissions', $this->validPayload([
            'email' => 'not-an-email',
            'audio' => $this->fakeAudio(),
        ]))->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_missing_phone_returns_422(): void
    {
        $this->postJson('/api/public/talent-submissions', $this->validPayload([
            'phone' => '',
            'audio' => $this->fakeAudio(),
        ]))->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    public function test_missing_talent_category_returns_422(): void
    {
        $this->postJson('/api/public/talent-submissions', $this->validPayload([
            'talent_category' => '',
            'audio' => $this->fakeAudio(),
        ]))->assertStatus(422)
            ->assertJsonValidationErrors(['talent_category']);
    }

    public function test_invalid_talent_category_returns_422(): void
    {
        $this->postJson('/api/public/talent-submissions', $this->validPayload([
            'talent_category' => 'Astronaut',
            'audio' => $this->fakeAudio(),
        ]))->assertStatus(422)
            ->assertJsonValidationErrors(['talent_category']);
    }

    public function test_missing_consent_returns_422(): void
    {
        $this->postJson('/api/public/talent-submissions', $this->validPayload([
            'consent' => '0',
            'audio' => $this->fakeAudio(),
        ]))->assertStatus(422)
            ->assertJsonValidationErrors(['consent']);
    }

    // ---------------------------------------------------------------
    // File validation
    // ---------------------------------------------------------------

    public function test_invalid_audio_mime_returns_422(): void
    {
        $badFile = UploadedFile::fake()->create('malware.exe', 100, 'application/octet-stream');

        $this->postJson('/api/public/talent-submissions', $this->validPayload([
            'audio' => $badFile,
        ]))->assertStatus(422)
            ->assertJsonValidationErrors(['audio']);
    }

    public function test_invalid_image_mime_returns_422(): void
    {
        $badFile = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $this->postJson('/api/public/talent-submissions', $this->validPayload([
            'image' => $badFile,
        ]))->assertStatus(422)
            ->assertJsonValidationErrors(['image']);
    }

    public function test_oversized_audio_is_rejected(): void
    {
        // 60 MB audio — exceeds the 50 MB rule
        $big = UploadedFile::fake()->create('huge.mp3', 60 * 1024, 'audio/mpeg');

        $this->postJson('/api/public/talent-submissions', $this->validPayload([
            'audio' => $big,
        ]))->assertStatus(422)
            ->assertJsonValidationErrors(['audio']);
    }

    public function test_oversized_image_is_rejected(): void
    {
        // 12 MB image — exceeds the 10 MB rule
        $big = UploadedFile::fake()->image('huge.jpg')->size(12 * 1024);

        $this->postJson('/api/public/talent-submissions', $this->validPayload([
            'image' => $big,
        ]))->assertStatus(422)
            ->assertJsonValidationErrors(['image']);
    }

    // ---------------------------------------------------------------
    // Storage privacy
    // ---------------------------------------------------------------

    public function test_files_are_stored_on_local_disk_not_public(): void
    {
        $this->postJson('/api/public/talent-submissions', $this->validPayload([
            'audio' => $this->fakeAudio(),
        ]))->assertStatus(201);

        $path = TalentSubmission::first()->audio_path;

        Storage::disk('local')->assertExists($path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_response_does_not_leak_internal_paths(): void
    {
        $response = $this->postJson('/api/public/talent-submissions', $this->validPayload([
            'audio' => $this->fakeAudio(),
        ]))->assertStatus(201);

        $body = $response->getContent();

        $this->assertStringNotContainsString('audio_path', $body);
        $this->assertStringNotContainsString('video_path', $body);
        $this->assertStringNotContainsString('image_path', $body);
        $this->assertStringNotContainsString('talent-submissions/', $body);
    }

    // ---------------------------------------------------------------
    // Public access
    // ---------------------------------------------------------------

    public function test_endpoint_is_public_and_requires_no_auth(): void
    {
        // No Sanctum::actingAs() — we're an anonymous visitor
        $this->postJson('/api/public/talent-submissions', $this->validPayload([
            'audio' => $this->fakeAudio(),
        ]))->assertStatus(201);
    }

    // ---------------------------------------------------------------
    // Rate limiting
    // ---------------------------------------------------------------

    public function test_rate_limiter_blocks_excessive_submissions(): void
    {
        // The route uses throttle:5,60 — 5 per minute.
        // We fire 5 allowed requests, then expect the 6th to be 429.

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/public/talent-submissions', $this->validPayload([
                'email' => "user{$i}@example.com",
                'audio' => $this->fakeAudio(),
            ]))->assertStatus(201);
        }

        $this->postJson('/api/public/talent-submissions', $this->validPayload([
            'email' => 'user6@example.com',
            'audio' => $this->fakeAudio(),
        ]))->assertStatus(429);
    }
}