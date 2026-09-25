<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\LoginDevice;
use App\Models\Role;
use App\Models\User;
use App\Notifications\LoginNotification;
use App\Notifications\SuspiciousLoginNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SuspiciousLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(string $email = 'user@kayfactory.test'): User
    {
        $role = Role::firstOrCreate(['slug' => 'label-manager'], ['name' => 'Label Manager']);

        return User::create([
            'name' => 'Test User',
            'email' => $email,
            'password' => 'Password123!',
            'role_id' => $role->id,
        ]);
    }

    /**
     * Stub ipapi.co to return a fixed country for a given public IP.
     */
    protected function fakeCountry(string $ip, string $countryCode, string $countryName, string $city = 'City'): void
    {
        Http::fake([
            "https://ipapi.co/{$ip}/json/" => Http::response([
                'city'         => $city,
                'region'       => 'Region',
                'country_name' => $countryName,
                'country_code' => $countryCode,
            ], 200),
        ]);
    }

    protected function loginFromIp(string $ip, string $email): \Illuminate\Testing\TestResponse
    {
        // Force the request's REMOTE_ADDR so $request->ip() returns our fake IP
        return $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->postJson('/api/login', [
                'email' => $email,
                'password' => 'Password123!',
            ]);
    }

    /* ============================================================
       Baseline — no alert on first / same-country logins
       ============================================================ */

    public function test_first_ever_login_does_not_alert(): void
    {
        Notification::fake();
        $this->makeUser();
        $this->fakeCountry('102.89.34.12', 'NG', 'Nigeria', 'Lagos');

        $this->loginFromIp('102.89.34.12', 'user@kayfactory.test')->assertOk();

        Notification::assertNotSentTo(
            User::first(),
            SuspiciousLoginNotification::class
        );
    }

    public function test_second_login_same_country_does_not_alert(): void
    {
        Notification::fake();
        $user = $this->makeUser();

        // Seed history: user has already logged in from Nigeria
        LoginDevice::create([
            'user_id'      => $user->id,
            'device_hash'  => hash('sha256', 'prior'),
            'browser'      => 'Chrome',
            'platform'     => 'Windows',
            'device'       => 'Desktop',
            'ip_address'   => '102.89.34.12',
            'location'     => 'Lagos, Nigeria',
            'country_code' => 'NG',
            'first_seen_at' => now()->subDay(),
            'last_seen_at'  => now()->subDay(),
        ]);

        $this->fakeCountry('102.89.34.13', 'NG', 'Nigeria', 'Abuja');

        $this->loginFromIp('102.89.34.13', 'user@kayfactory.test')->assertOk();

        Notification::assertNotSentTo($user, SuspiciousLoginNotification::class);
    }

    /* ============================================================
       Detection — new country triggers alert
       ============================================================ */

    public function test_new_country_triggers_suspicious_login_notification(): void
    {
        Notification::fake();
        $user = $this->makeUser();

        LoginDevice::create([
            'user_id'      => $user->id,
            'device_hash'  => hash('sha256', 'prior'),
            'browser'      => 'Chrome',
            'platform'     => 'Windows',
            'device'       => 'Desktop',
            'ip_address'   => '102.89.34.12',
            'location'     => 'Lagos, Nigeria',
            'country_code' => 'NG',
            'first_seen_at' => now()->subDay(),
            'last_seen_at'  => now()->subDay(),
        ]);

        $this->fakeCountry('8.8.8.8', 'US', 'United States', 'Mountain View');

        $this->loginFromIp('8.8.8.8', 'user@kayfactory.test')->assertOk();

        Notification::assertSentTo($user, SuspiciousLoginNotification::class);
        Notification::assertNotSentTo($user, LoginNotification::class);
    }

    public function test_new_country_writes_suspicious_login_audit_event(): void
    {
        Notification::fake();
        $user = $this->makeUser();

        LoginDevice::create([
            'user_id'      => $user->id,
            'device_hash'  => hash('sha256', 'prior'),
            'browser'      => 'Chrome',
            'platform'     => 'Windows',
            'device'       => 'Desktop',
            'ip_address'   => '102.89.34.12',
            'location'     => 'Lagos, Nigeria',
            'country_code' => 'NG',
            'first_seen_at' => now()->subDay(),
            'last_seen_at'  => now()->subDay(),
        ]);

        $this->fakeCountry('8.8.8.8', 'US', 'United States');

        $this->loginFromIp('8.8.8.8', 'user@kayfactory.test')->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action'  => 'suspicious_login_detected',
        ]);
    }

    public function test_new_country_suppresses_regular_new_device_notification(): void
    {
        Notification::fake();
        $user = $this->makeUser();

        LoginDevice::create([
            'user_id'      => $user->id,
            'device_hash'  => hash('sha256', 'prior'),
            'browser'      => 'Chrome',
            'platform'     => 'Windows',
            'device'       => 'Desktop',
            'ip_address'   => '102.89.34.12',
            'location'     => 'Lagos, Nigeria',
            'country_code' => 'NG',
            'first_seen_at' => now()->subDay(),
            'last_seen_at'  => now()->subDay(),
        ]);

        $this->fakeCountry('8.8.8.8', 'US', 'United States');

        $this->loginFromIp('8.8.8.8', 'user@kayfactory.test')->assertOk();

        Notification::assertSentTo($user, SuspiciousLoginNotification::class);
        Notification::assertNotSentTo($user, LoginNotification::class);
    }

    public function test_new_country_with_existing_device_still_alerts(): void
    {
        Notification::fake();
        $user = $this->makeUser();

        // Same device_hash as our test login will produce (from 8.8.8.8 / Chrome / Windows)
        $priorHash = LoginDevice::generateHash('8.8.8.8', 'Chrome', 'Windows 10/11');

        LoginDevice::create([
            'user_id'      => $user->id,
            'device_hash'  => $priorHash,
            'browser'      => 'Chrome',
            'platform'     => 'Windows 10/11',
            'device'       => 'Desktop',
            'ip_address'   => '102.89.34.12',
            'location'     => 'Lagos, Nigeria',
            'country_code' => 'NG',
            'first_seen_at' => now()->subDay(),
            'last_seen_at'  => now()->subDay(),
        ]);

        $this->fakeCountry('8.8.8.8', 'US', 'United States');

        // Force the User-Agent to match Chrome/Windows
        $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36',
        ])->withServerVariables(['REMOTE_ADDR' => '8.8.8.8'])
          ->postJson('/api/login', [
              'email' => 'user@kayfactory.test',
              'password' => 'Password123!',
          ])->assertOk();

        Notification::assertSentTo($user, SuspiciousLoginNotification::class);
    }

    /* ============================================================
       Guardrails — nulls, private IPs, no false positives
       ============================================================ */

    public function test_null_country_code_does_not_alert(): void
    {
        Notification::fake();
        $user = $this->makeUser();

        LoginDevice::create([
            'user_id'      => $user->id,
            'device_hash'  => hash('sha256', 'prior'),
            'browser'      => 'Chrome',
            'platform'     => 'Windows',
            'device'       => 'Desktop',
            'ip_address'   => '102.89.34.12',
            'location'     => 'Lagos, Nigeria',
            'country_code' => 'NG',
            'first_seen_at' => now()->subDay(),
            'last_seen_at'  => now()->subDay(),
        ]);

        // ipapi.co returns 200 but with no country_code — resolver should produce null
        Http::fake([
            'https://ipapi.co/8.8.8.8/json/' => Http::response([
                'city' => null,
                'region' => null,
                'country_name' => null,
            ], 200),
        ]);

        $this->loginFromIp('8.8.8.8', 'user@kayfactory.test')->assertOk();

        Notification::assertNotSentTo($user, SuspiciousLoginNotification::class);
    }

    public function test_geolocation_service_failure_does_not_block_login(): void
    {
        Notification::fake();
        $user = $this->makeUser();

        Http::fake([
            'https://ipapi.co/*' => Http::response('Server error', 500),
        ]);

        // Login must succeed despite geolocation failure
        $this->loginFromIp('8.8.8.8', 'user@kayfactory.test')->assertOk();

        Notification::assertNotSentTo($user, SuspiciousLoginNotification::class);
    }

    public function test_local_network_ip_never_alerts(): void
    {
        Notification::fake();
        $user = $this->makeUser();

        LoginDevice::create([
            'user_id'      => $user->id,
            'device_hash'  => hash('sha256', 'prior'),
            'browser'      => 'Chrome',
            'platform'     => 'Windows',
            'device'       => 'Desktop',
            'ip_address'   => '102.89.34.12',
            'location'     => 'Lagos, Nigeria',
            'country_code' => 'NG',
            'first_seen_at' => now()->subDay(),
            'last_seen_at'  => now()->subDay(),
        ]);

        // 127.0.0.1 → resolver short-circuits to "Local Network" with null country
        $this->loginFromIp('127.0.0.1', 'user@kayfactory.test')->assertOk();

        Notification::assertNotSentTo($user, SuspiciousLoginNotification::class);
    }

    /* ============================================================
       Payload hygiene
       ============================================================ */

    public function test_suspicious_notification_payload_contains_no_secrets(): void
    {
        Notification::fake();
        $user = $this->makeUser();

        LoginDevice::create([
            'user_id'      => $user->id,
            'device_hash'  => hash('sha256', 'prior'),
            'browser'      => 'Chrome',
            'platform'     => 'Windows',
            'device'       => 'Desktop',
            'ip_address'   => '102.89.34.12',
            'location'     => 'Lagos, Nigeria',
            'country_code' => 'NG',
            'first_seen_at' => now()->subDay(),
            'last_seen_at'  => now()->subDay(),
        ]);

        $this->fakeCountry('8.8.8.8', 'US', 'United States');

        $this->loginFromIp('8.8.8.8', 'user@kayfactory.test')->assertOk();

        Notification::assertSentTo($user, SuspiciousLoginNotification::class, function ($notification) use ($user) {
            $arr = $notification->toDatabase($user);
            $raw = json_encode($arr);

            $this->assertStringNotContainsString('Password123!', $raw);
            $this->assertArrayNotHasKey('password', $arr);
            $this->assertArrayNotHasKey('token', $arr);
            $this->assertArrayNotHasKey('secret', $arr);

            return true;
        });
    }
}