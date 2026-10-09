<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfileTest extends TestCase
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

        return User::factory()->create([
            'role_id' => $role->id,
        ]);
    }

    // =============================================================
    // Update profile — name
    // =============================================================

    public function test_user_can_update_own_name(): void
    {
        $user = $this->userWithRole('general-staff');
        Sanctum::actingAs($user);

        $this->patchJson('/api/me', ['name' => 'New Name'])
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'New Name');

        $this->assertEquals('New Name', $user->fresh()->name);
    }

    public function test_unauthenticated_cannot_update_profile(): void
    {
        $this->patchJson('/api/me', ['name' => 'X'])
            ->assertStatus(401);
    }

    public function test_name_validation(): void
    {
        $user = $this->userWithRole('general-staff');
        Sanctum::actingAs($user);

        $this->patchJson('/api/me', [
            'name' => str_repeat('a', 300),
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    // =============================================================
    // Update profile — email
    // =============================================================

    public function test_user_can_change_email_with_correct_password(): void
    {
        $user = $this->userWithRole('general-staff');
        Sanctum::actingAs($user);

        $this->patchJson('/api/me', [
            'email' => 'newemail@example.com',
            'current_password' => 'password',
        ])
            ->assertStatus(200)
            ->assertJsonPath('data.email', 'newemail@example.com');

        $this->assertEquals(
            'newemail@example.com',
            $user->fresh()->email
        );
    }

    public function test_changing_email_requires_current_password(): void
    {
        $user = $this->userWithRole('general-staff');
        Sanctum::actingAs($user);

        $this->patchJson('/api/me', [
            'email' => 'new@example.com',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['current_password']);
    }

    public function test_changing_email_with_wrong_password_is_rejected(): void
    {
        $user = $this->userWithRole('general-staff');
        Sanctum::actingAs($user);

        $this->patchJson('/api/me', [
            'email' => 'new@example.com',
            'current_password' => 'wrong-password',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['current_password']);
    }

    public function test_same_email_does_not_require_password(): void
    {
        $user = $this->userWithRole('general-staff');
        Sanctum::actingAs($user);

        $this->patchJson('/api/me', [
            'name' => 'Same Email Test',
            'email' => $user->email,
        ])
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'Same Email Test');
    }

    public function test_email_must_be_unique(): void
    {
        $other = $this->userWithRole('general-staff');
        $user = $this->userWithRole('general-staff');
        Sanctum::actingAs($user);

        $this->patchJson('/api/me', [
            'email' => $other->email,
            'current_password' => 'password',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    // =============================================================
    // Avatar upload
    // =============================================================

    public function test_user_can_upload_avatar(): void
    {
        Storage::fake('supabase');

        $user = $this->userWithRole('general-staff');
        Sanctum::actingAs($user);

        $file = UploadedFile::fake()->image('photo.jpg', 400, 400);

        $response = $this->postJson('/api/me/avatar', [
            'avatar' => $file,
        ])->assertStatus(200);

        $user->refresh();

        $this->assertNotNull($user->avatar);

        Storage::disk('supabase')->assertExists($user->avatar);

        $this->assertStringContainsString(
            '/storage/v1/object/public/',
            $response->json('user.avatar_url')
        );
    }

    public function test_invalid_file_type_is_rejected(): void
    {
        Storage::fake('supabase');

        $user = $this->userWithRole('general-staff');
        Sanctum::actingAs($user);

        $file = UploadedFile::fake()->create(
            'doc.pdf',
            100,
            'application/pdf'
        );

        $this->postJson('/api/me/avatar', [
            'avatar' => $file,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['avatar']);
    }

    public function test_oversized_avatar_is_rejected(): void
    {
        Storage::fake('supabase');

        $user = $this->userWithRole('general-staff');
        Sanctum::actingAs($user);

        // 3 MB exceeds the 2 MB limit.
        $file = UploadedFile::fake()
            ->image('big.jpg')
            ->size(3000);

        $this->postJson('/api/me/avatar', [
            'avatar' => $file,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['avatar']);
    }

    public function test_avatar_replacement_removes_old_file(): void
    {
        Storage::fake('supabase');

        $user = $this->userWithRole('general-staff');
        Sanctum::actingAs($user);

        // First upload.
        $this->postJson('/api/me/avatar', [
            'avatar' => UploadedFile::fake()->image('first.jpg'),
        ])->assertStatus(200);

        $user->refresh();
        $firstPath = $user->avatar;

        Storage::disk('supabase')->assertExists($firstPath);

        // Replace the first avatar.
        $this->postJson('/api/me/avatar', [
            'avatar' => UploadedFile::fake()->image('second.jpg'),
        ])->assertStatus(200);

        $user->refresh();
        $secondPath = $user->avatar;

        $this->assertNotEquals($firstPath, $secondPath);

        Storage::disk('supabase')->assertMissing($firstPath);
        Storage::disk('supabase')->assertExists($secondPath);
    }

    // =============================================================
    // Avatar removal
    // =============================================================

    public function test_user_can_remove_avatar(): void
    {
        Storage::fake('supabase');

        $user = $this->userWithRole('general-staff');
        Sanctum::actingAs($user);

        $this->postJson('/api/me/avatar', [
            'avatar' => UploadedFile::fake()->image('a.jpg'),
        ])->assertStatus(200);

        $user->refresh();
        $path = $user->avatar;

        $this->assertNotNull($path);
        Storage::disk('supabase')->assertExists($path);

        $this->deleteJson('/api/me/avatar')
            ->assertStatus(200);

        $user->refresh();

        $this->assertNull($user->avatar);
        Storage::disk('supabase')->assertMissing($path);
    }

    public function test_removing_avatar_when_none_exists_is_safe(): void
    {
        $user = $this->userWithRole('general-staff');
        Sanctum::actingAs($user);

        $this->deleteJson('/api/me/avatar')
            ->assertStatus(200);

        $this->assertNull($user->fresh()->avatar);
    }

    // =============================================================
    // Authorization — cannot touch another user
    // =============================================================

    public function test_user_cannot_update_another_users_profile(): void
    {
        $other = $this->userWithRole('general-staff');
        $user = $this->userWithRole('general-staff');

        Sanctum::actingAs($user);

        // The endpoint has no user-ID parameter.
        $originalName = $other->name;

        $this->patchJson('/api/me', [
            'name' => 'Hacker',
        ])->assertStatus(200);

        $this->assertEquals($originalName, $other->fresh()->name);
        $this->assertEquals('Hacker', $user->fresh()->name);
    }

    // =============================================================
    // Audit logging
    // =============================================================

    public function test_profile_update_creates_audit_entry(): void
    {
        $user = $this->userWithRole('general-staff');
        Sanctum::actingAs($user);

        $this->patchJson('/api/me', [
            'name' => 'Audited Name',
        ])->assertStatus(200);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'profile_updated',
            'model_type' => 'User',
            'model_id' => $user->id,
        ]);

        $log = AuditLog::where('action', 'profile_updated')
            ->latest('id')
            ->first();

        $this->assertNotNull($log);

        $this->assertEquals(
            'Audited Name',
            $log->changes['after']['name'] ?? null
        );

        $this->assertArrayNotHasKey(
            'password',
            $log->changes['after'] ?? []
        );
    }

    public function test_avatar_upload_creates_audit_entry(): void
    {
        Storage::fake('supabase');

        $user = $this->userWithRole('general-staff');
        Sanctum::actingAs($user);

        $this->postJson('/api/me/avatar', [
            'avatar' => UploadedFile::fake()->image('a.jpg'),
        ])->assertStatus(200);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'avatar_updated',
            'model_id' => $user->id,
        ]);
    }

    public function test_avatar_removal_creates_audit_entry(): void
    {
        Storage::fake('supabase');

        $user = $this->userWithRole('general-staff');
        Sanctum::actingAs($user);

        $this->postJson('/api/me/avatar', [
            'avatar' => UploadedFile::fake()->image('a.jpg'),
        ])->assertStatus(200);

        $this->deleteJson('/api/me/avatar')
            ->assertStatus(200);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'avatar_removed',
            'model_id' => $user->id,
        ]);
    }

    public function test_audit_logs_never_contain_password_fields(): void
    {
        $user = $this->userWithRole('general-staff');
        Sanctum::actingAs($user);

        $this->patchJson('/api/me', [
            'email' => 'new@example.com',
            'current_password' => 'password',
        ])->assertStatus(200);

        $logs = AuditLog::all();

        $raw = $logs->pluck('changes')
            ->map(fn ($changes) => json_encode($changes))
            ->implode(' ');

        $this->assertStringNotContainsString('"current_password"', $raw);
        $this->assertStringNotContainsString('"password"', $raw);
    }
}
