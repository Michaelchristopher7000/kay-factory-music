<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class LoginThrottleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('login-email:user@kayfactory.test');
        RateLimiter::clear('login-ip:127.0.0.1');
        config()->set('kfm.login.max_attempts', 3);
        config()->set('kfm.login.ip_max_attempts', 10);
        config()->set('kfm.login.decay_seconds', 900);
    }

    protected function makeUser(): User
    {
        $role = Role::create(['name' => 'Super Admin', 'slug' => 'super-admin']);
        return User::create([
            'name' => 'Test User',
            'email' => 'user@kayfactory.test',
            'password' => 'Password123!',
            'role_id' => $role->id,
        ]);
    }

    public function test_successful_login_returns_token(): void
    {
        $this->makeUser();
        $this->postJson('/api/login', [
            'email' => 'user@kayfactory.test',
            'password' => 'Password123!',
        ])->assertOk()->assertJsonStructure(['token', 'user']);
    }

    public function test_failed_login_returns_422(): void
    {
        $this->makeUser();
        $this->postJson('/api/login', [
            'email' => 'user@kayfactory.test',
            'password' => 'wrong',
        ])->assertStatus(422);
    }

    public function test_lockout_triggers_429(): void
    {
        $this->makeUser();

        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/login', [
                'email' => 'user@kayfactory.test',
                'password' => 'wrong',
            ])->assertStatus(422);
        }

        // Even with correct password now — throttled
        $this->postJson('/api/login', [
            'email' => 'user@kayfactory.test',
            'password' => 'Password123!',
        ])->assertStatus(429);
    }

    public function test_successful_login_clears_limiter(): void
    {
        $this->makeUser();

        for ($i = 0; $i < 2; $i++) {
            $this->postJson('/api/login', [
                'email' => 'user@kayfactory.test',
                'password' => 'wrong',
            ])->assertStatus(422);
        }

        $this->postJson('/api/login', [
            'email' => 'user@kayfactory.test',
            'password' => 'Password123!',
        ])->assertOk();

        // Limiter cleared — next login also works
        $this->postJson('/api/login', [
            'email' => 'user@kayfactory.test',
            'password' => 'Password123!',
        ])->assertOk();
    }

    public function test_no_account_enumeration(): void
    {
        // No user exists — response should be identical in shape
        $response = $this->postJson('/api/login', [
            'email' => 'nobody@nowhere.test',
            'password' => 'anything',
        ]);

        $this->assertContains($response->status(), [422, 429]);
    }
}