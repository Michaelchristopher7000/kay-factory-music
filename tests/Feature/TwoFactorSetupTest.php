<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorSetupTest extends TestCase
{
    use RefreshDatabase;

    protected function makeSuperAdmin(string $email = 'admin@kayfactory.test'): User
    {
        $role = Role::firstOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);

        return User::create([
            'name' => 'Super Admin',
            'email' => $email,
            'password' => 'Password123!',
            'role_id' => $role->id,
        ]);
    }

    protected function generateValidCode(string $secret): string
    {
        return app(Google2FA::class)->getCurrentOtp($secret);
    }

    public function test_super_admin_can_start_setup(): void
    {
        $user = $this->makeSuperAdmin();
        Sanctum::actingAs($user, ['*'], 'sanctum');

        $response = $this->postJson('/api/me/2fa/setup');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['secret', 'qr_code', 'otpauth_url'],
            ]);

        $user->refresh();
        $this->assertNotNull($user->two_factor_secret);
        $this->assertNull($user->two_factor_confirmed_at);
    }

    public function test_secret_is_stored_encrypted(): void
    {
        $user = $this->makeSuperAdmin();
        Sanctum::actingAs($user, ['*'], 'sanctum');

        $this->postJson('/api/me/2fa/setup')->assertOk();

        // Fetch the raw DB value — must NOT equal the plain secret
        $rawSecret = \DB::table('users')->where('id', $user->id)->value('two_factor_secret');
        $user->refresh();

        $this->assertNotSame($user->two_factor_secret, $rawSecret);
        $this->assertNotSame('', $rawSecret);
    }

    public function test_setup_started_audit_event_written(): void
    {
        $user = $this->makeSuperAdmin();
        Sanctum::actingAs($user, ['*'], 'sanctum');

        $this->postJson('/api/me/2fa/setup')->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action'  => 'two_factor_setup_started',
        ]);
    }

    public function test_valid_code_confirms_setup(): void
    {
        $user = $this->makeSuperAdmin();
        Sanctum::actingAs($user, ['*'], 'sanctum');

        $setup = $this->postJson('/api/me/2fa/setup')->assertOk();
        $secret = $setup->json('data.secret');

        $code = $this->generateValidCode($secret);

        $response = $this->postJson('/api/me/2fa/verify', ['code' => $code]);

        $response->assertOk()
            ->assertJsonStructure(['message', 'recovery_codes']);

        $this->assertCount(8, $response->json('recovery_codes'));

        $user->refresh();
        $this->assertNotNull($user->two_factor_confirmed_at);
        $this->assertTrue($user->hasTwoFactorEnabled());
    }

    public function test_invalid_code_does_not_confirm_setup(): void
    {
        $user = $this->makeSuperAdmin();
        Sanctum::actingAs($user, ['*'], 'sanctum');

        $this->postJson('/api/me/2fa/setup')->assertOk();

        $this->postJson('/api/me/2fa/verify', ['code' => '000000'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['code']);

        $user->refresh();
        $this->assertNull($user->two_factor_confirmed_at);
        $this->assertFalse($user->hasTwoFactorEnabled());
    }

    public function test_two_factor_enabled_audit_event_written(): void
    {
        $user = $this->makeSuperAdmin();
        Sanctum::actingAs($user, ['*'], 'sanctum');

        $setup = $this->postJson('/api/me/2fa/setup')->assertOk();
        $code = $this->generateValidCode($setup->json('data.secret'));

        $this->postJson('/api/me/2fa/verify', ['code' => $code])->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action'  => 'two_factor_enabled',
        ]);
    }

    public function test_setup_rejected_when_already_confirmed(): void
    {
        $user = $this->makeSuperAdmin();
        Sanctum::actingAs($user, ['*'], 'sanctum');

        // Enable 2FA first
        $setup = $this->postJson('/api/me/2fa/setup')->assertOk();
        $code = $this->generateValidCode($setup->json('data.secret'));
        $this->postJson('/api/me/2fa/verify', ['code' => $code])->assertOk();

        // Now attempt setup again — should be rejected
        $this->postJson('/api/me/2fa/setup')
            ->assertStatus(422)
            ->assertJsonPath('message', 'Two-factor authentication is already enabled. Disable it first to re-enroll.');
    }

    public function test_unconfirmed_setup_can_be_restarted(): void
    {
        $user = $this->makeSuperAdmin();
        Sanctum::actingAs($user, ['*'], 'sanctum');

        // First setup — do NOT verify
        $first = $this->postJson('/api/me/2fa/setup')->assertOk();
        $firstSecret = $first->json('data.secret');

        // Second setup — should regenerate
        $second = $this->postJson('/api/me/2fa/setup')->assertOk();
        $secondSecret = $second->json('data.secret');

        $this->assertNotSame($firstSecret, $secondSecret);
    }

    public function test_qr_code_is_data_uri(): void
    {
        $user = $this->makeSuperAdmin();
        Sanctum::actingAs($user, ['*'], 'sanctum');

        $response = $this->postJson('/api/me/2fa/setup')->assertOk();

        $qr = $response->json('data.qr_code');
        $this->assertStringStartsWith('data:image/svg+xml;base64,', $qr);

        // Verify it decodes as valid SVG
        $decoded = base64_decode(substr($qr, strlen('data:image/svg+xml;base64,')));
        $this->assertStringContainsString('<svg', $decoded);
    }
}