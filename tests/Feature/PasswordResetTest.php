<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
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
    // Forgot password — request
    // =============================================================

    public function test_can_request_password_reset_for_existing_email(): void
    {
        Notification::fake();

        $user = $this->userWithRole('general-staff');

        $this->postJson('/api/forgot-password', ['email' => $user->email])
            ->assertStatus(200)
            ->assertJsonPath(
                'message',
                'If an account exists for that email address, a password reset link has been sent.'
            );

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_forgot_password_response_does_not_reveal_if_account_exists(): void
    {
        Notification::fake();

        // Unknown email — same generic response
        $this->postJson('/api/forgot-password', ['email' => 'nobody@example.com'])
            ->assertStatus(200)
            ->assertJsonPath(
                'message',
                'If an account exists for that email address, a password reset link has been sent.'
            );

        Notification::assertNothingSent();
    }

    public function test_forgot_password_requires_valid_email(): void
    {
        $this->postJson('/api/forgot-password', ['email' => 'not-an-email'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_forgot_password_requires_email_field(): void
    {
        $this->postJson('/api/forgot-password', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    // =============================================================
    // Reset password — success
    // =============================================================

    public function test_user_can_reset_password_with_valid_token(): void
    {
        $user = $this->userWithRole('general-staff');
        $token = Password::broker()->createToken($user);

        $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'new-reset-pass-2026',
            'password_confirmation' => 'new-reset-pass-2026',
        ])->assertStatus(200);

        $this->assertTrue(Hash::check('new-reset-pass-2026', $user->fresh()->password));
        $this->assertFalse(Hash::check('password', $user->fresh()->password));
    }

    public function test_successful_reset_invalidates_the_token(): void
    {
        $user = $this->userWithRole('general-staff');
        $token = Password::broker()->createToken($user);

        // Confirm token exists
        $this->assertDatabaseHas('password_reset_tokens', ['email' => $user->email]);

        $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'new-reset-pass-2026',
            'password_confirmation' => 'new-reset-pass-2026',
        ])->assertStatus(200);

        // Token row removed
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);

        // Reusing the token fails
        $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'another-pass-2026',
            'password_confirmation' => 'another-pass-2026',
        ])->assertStatus(422);
    }

    // =============================================================
    // Reset password — rejections
    // =============================================================

    public function test_invalid_token_is_rejected(): void
    {
        $user = $this->userWithRole('general-staff');

        $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'token' => 'completely-invalid-token',
            'password' => 'new-pass-2026',
            'password_confirmation' => 'new-pass-2026',
        ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'This password reset link is invalid or has expired.');
    }

    public function test_expired_token_is_rejected(): void
    {
        config(['auth.passwords.users.expire' => 60]);

        $user = $this->userWithRole('general-staff');
        $token = Password::broker()->createToken($user);

        // Backdate the token beyond the expiry window
        DB::table('password_reset_tokens')
            ->where('email', $user->email)
            ->update(['created_at' => now()->subMinutes(120)]);

        $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'new-pass-2026',
            'password_confirmation' => 'new-pass-2026',
        ])->assertStatus(422);
    }

    public function test_missing_token_is_rejected(): void
    {
        $user = $this->userWithRole('general-staff');

        $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'password' => 'new-pass-2026',
            'password_confirmation' => 'new-pass-2026',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['token']);
    }

    public function test_password_confirmation_mismatch_is_rejected(): void
    {
        $user = $this->userWithRole('general-staff');
        $token = Password::broker()->createToken($user);

        $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'new-pass-2026',
            'password_confirmation' => 'different-pass',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_short_password_is_rejected(): void
    {
        $user = $this->userWithRole('general-staff');
        $token = Password::broker()->createToken($user);

        $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'short',
            'password_confirmation' => 'short',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_unknown_email_is_rejected(): void
    {
        $this->postJson('/api/reset-password', [
            'email' => 'nobody@example.com',
            'token' => 'anything',
            'password' => 'new-pass-2026',
            'password_confirmation' => 'new-pass-2026',
        ])->assertStatus(422);
    }

    // =============================================================
    // Security
    // =============================================================

    public function test_password_is_hashed_after_reset(): void
    {
        $user = $this->userWithRole('general-staff');
        $token = Password::broker()->createToken($user);

        $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'plaintext-plain-2026',
            'password_confirmation' => 'plaintext-plain-2026',
        ])->assertStatus(200);

        $stored = $user->fresh()->password;
        $this->assertNotEquals('plaintext-plain-2026', $stored);
        $this->assertTrue(Hash::check('plaintext-plain-2026', $stored));
    }

    public function test_response_does_not_leak_password(): void
    {
        $user = $this->userWithRole('general-staff');
        $token = Password::broker()->createToken($user);

        $response = $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'leak-test-pass-2026',
            'password_confirmation' => 'leak-test-pass-2026',
        ])->assertStatus(200);

        $raw = json_encode($response->json());
        $this->assertStringNotContainsString('leak-test-pass-2026', $raw);
        $this->assertStringNotContainsString($user->fresh()->password, $raw);
    }

    // =============================================================
    // Audit log
    // =============================================================

    public function test_successful_reset_creates_audit_entry(): void
    {
        $user = $this->userWithRole('general-staff');
        $token = Password::broker()->createToken($user);

        $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'new-pass-2026',
            'password_confirmation' => 'new-pass-2026',
        ])->assertStatus(200);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'password_reset',
            'model_type' => 'User',
            'model_id' => $user->id,
        ]);
    }

    public function test_audit_log_never_contains_password_or_token(): void
    {
        $user = $this->userWithRole('general-staff');
        $token = Password::broker()->createToken($user);

        $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'top-secret-reset-2026',
            'password_confirmation' => 'top-secret-reset-2026',
        ])->assertStatus(200);

        $logs = AuditLog::all();
        $raw = $logs->pluck('changes')->map(fn ($c) => json_encode($c))->implode(' ');

        $this->assertStringNotContainsString('top-secret-reset-2026', $raw);
        $this->assertStringNotContainsString($token, $raw);
        $this->assertStringNotContainsString('"password"', $raw);
        $this->assertStringNotContainsString('"password_confirmation"', $raw);
    }
}