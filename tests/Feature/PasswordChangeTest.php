<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PasswordChangeTest extends TestCase
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
            'password' => Hash::make('password'),
        ]);
    }

    // =============================================================
    // Auth
    // =============================================================

    public function test_unauthenticated_request_returns_401(): void
    {
        $this->postJson('/api/me/password', [])->assertStatus(401);
    }

    // =============================================================
    // Happy path
    // =============================================================

    public function test_user_can_change_password_with_correct_current_password(): void
    {
        $user = $this->userWithRole('general-staff');
        Sanctum::actingAs($user);

        $this->postJson('/api/me/password', [
            'current_password' => 'password',
            'password' => 'new-secret-pass-2026',
            'password_confirmation' => 'new-secret-pass-2026',
        ])
            ->assertStatus(200)
            ->assertJsonPath('message', 'Password changed successfully.');

        $this->assertTrue(Hash::check('new-secret-pass-2026', $user->fresh()->password));
        $this->assertFalse(Hash::check('password', $user->fresh()->password));
    }

    public function test_user_can_log_in_with_new_password_after_change(): void
    {
        $user = $this->userWithRole('general-staff');

        // Change password via the API while authenticated
        Sanctum::actingAs($user);
        $this->postJson('/api/me/password', [
            'current_password' => 'password',
            'password' => 'brand-new-pass-2026',
            'password_confirmation' => 'brand-new-pass-2026',
        ])->assertStatus(200);

        // Reset the acting user and guard so we can attempt a fresh login
        $this->app['auth']->forgetGuards();

        // Verify the new hash directly against the DB
        $this->assertTrue(Hash::check('brand-new-pass-2026', $user->fresh()->password));
        $this->assertFalse(Hash::check('password', $user->fresh()->password));

        // Verify Auth::guard('web')->attempt works — same code path AuthController uses
        $this->assertTrue(
            Auth::guard('web')->attempt([
                'email' => $user->email,
                'password' => 'brand-new-pass-2026',
            ])
        );

        // Reset and confirm the OLD password no longer authenticates
        $this->app['auth']->forgetGuards();
        $this->assertFalse(
            Auth::guard('web')->attempt([
                'email' => $user->email,
                'password' => 'password',
            ])
        );
    }

    // =============================================================
    // Rejections
    // =============================================================

    public function test_wrong_current_password_is_rejected(): void
    {
        $user = $this->userWithRole('general-staff');
        Sanctum::actingAs($user);

        $this->postJson('/api/me/password', [
            'current_password' => 'wrong-password',
            'password' => 'new-pass-2026',
            'password_confirmation' => 'new-pass-2026',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['current_password']);

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_missing_current_password_is_rejected(): void
    {
        $user = $this->userWithRole('general-staff');
        Sanctum::actingAs($user);

        $this->postJson('/api/me/password', [
            'password' => 'new-pass-2026',
            'password_confirmation' => 'new-pass-2026',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['current_password']);
    }

    public function test_password_confirmation_mismatch_is_rejected(): void
    {
        $user = $this->userWithRole('general-staff');
        Sanctum::actingAs($user);

        $this->postJson('/api/me/password', [
            'current_password' => 'password',
            'password' => 'new-pass-2026',
            'password_confirmation' => 'different-pass',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_short_password_is_rejected(): void
    {
        $user = $this->userWithRole('general-staff');
        Sanctum::actingAs($user);

        $this->postJson('/api/me/password', [
            'current_password' => 'password',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_missing_new_password_is_rejected(): void
    {
        $user = $this->userWithRole('general-staff');
        Sanctum::actingAs($user);

        $this->postJson('/api/me/password', [
            'current_password' => 'password',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    // =============================================================
    // Security — password never leaks
    // =============================================================

    public function test_response_does_not_contain_password(): void
    {
        $user = $this->userWithRole('general-staff');
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/me/password', [
            'current_password' => 'password',
            'password' => 'new-pass-2026',
            'password_confirmation' => 'new-pass-2026',
        ])->assertStatus(200);

        $raw = json_encode($response->json());

        $this->assertStringNotContainsString('new-pass-2026', $raw);
        $this->assertStringNotContainsString('password_confirmation', $raw);
        $this->assertStringNotContainsString($user->fresh()->password, $raw);
    }

    public function test_password_is_hashed_not_stored_plain(): void
    {
        $user = $this->userWithRole('general-staff');
        Sanctum::actingAs($user);

        $this->postJson('/api/me/password', [
            'current_password' => 'password',
            'password' => 'new-plain-pass-2026',
            'password_confirmation' => 'new-plain-pass-2026',
        ])->assertStatus(200);

        $stored = $user->fresh()->password;

        $this->assertNotEquals('new-plain-pass-2026', $stored);
        $this->assertTrue(Hash::check('new-plain-pass-2026', $stored));
    }

    // =============================================================
    // Audit log
    // =============================================================

    public function test_password_change_creates_audit_entry(): void
    {
        $user = $this->userWithRole('general-staff');
        Sanctum::actingAs($user);

        $this->postJson('/api/me/password', [
            'current_password' => 'password',
            'password' => 'new-pass-2026',
            'password_confirmation' => 'new-pass-2026',
        ])->assertStatus(200);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'password_changed',
            'model_type' => 'User',
            'model_id' => $user->id,
        ]);
    }

    public function test_audit_log_never_contains_passwords(): void
    {
        $user = $this->userWithRole('general-staff');
        Sanctum::actingAs($user);

        $this->postJson('/api/me/password', [
            'current_password' => 'password',
            'password' => 'top-secret-2026',
            'password_confirmation' => 'top-secret-2026',
        ])->assertStatus(200);

        $logs = AuditLog::all();
        $raw = $logs->pluck('changes')->map(fn ($c) => json_encode($c))->implode(' ');

        $this->assertStringNotContainsString('top-secret-2026', $raw);
        $this->assertStringNotContainsString('"password"', $raw);
        $this->assertStringNotContainsString('"current_password"', $raw);
        $this->assertStringNotContainsString('"password_confirmation"', $raw);
    }

    // =============================================================
    // Authorization — cannot change another user's password
    // =============================================================

    public function test_user_cannot_change_another_users_password(): void
    {
        $other = $this->userWithRole('general-staff');
        $actor = $this->userWithRole('general-staff');

        $originalOtherPassword = $other->password;

        Sanctum::actingAs($actor);

        $this->postJson('/api/me/password', [
            'current_password' => 'password',
            'password' => 'actor-new-pass',
            'password_confirmation' => 'actor-new-pass',
        ])->assertStatus(200);

        $this->assertEquals($originalOtherPassword, $other->fresh()->password);
    }
}