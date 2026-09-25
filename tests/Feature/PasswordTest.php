<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PasswordTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(): User
    {
        $role = Role::create(['name' => 'Super Admin', 'slug' => 'super-admin']);
        return User::create([
            'name' => 'Test User',
            'email' => 'user@kayfactory.test',
            'password' => 'OldPassword123!',
            'role_id' => $role->id,
        ]);
    }

    public function test_weak_password_rejected_on_change(): void
    {
        $user = $this->makeUser();
        Sanctum::actingAs($user, ['*'], 'sanctum');

        $this->postJson('/api/me/password', [
            'current_password' => 'OldPassword123!',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertStatus(422)->assertJsonValidationErrors(['password']);
    }

    public function test_strong_password_accepted_on_change(): void
    {
        $user = $this->makeUser();
        Sanctum::actingAs($user, ['*'], 'sanctum');

        $this->postJson('/api/me/password', [
            'current_password' => 'OldPassword123!',
            'password' => 'NewSecurePass123!',
            'password_confirmation' => 'NewSecurePass123!',
        ])->assertOk();
    }

    public function test_weak_password_rejected_on_reset(): void
    {
        $this->makeUser();

        $this->postJson('/api/reset-password', [
            'token' => 'fake-token',
            'email' => 'user@kayfactory.test',
            'password' => 'weak',
            'password_confirmation' => 'weak',
        ])->assertStatus(422)->assertJsonValidationErrors(['password']);
    }

    public function test_password_change_requires_current_password(): void
    {
        $user = $this->makeUser();
        Sanctum::actingAs($user, ['*'], 'sanctum');

        $this->postJson('/api/me/password', [
            'current_password' => 'WrongOldPassword!',
            'password' => 'NewSecurePass123!',
            'password_confirmation' => 'NewSecurePass123!',
        ])->assertStatus(422);
    }

    public function test_password_never_returned_by_api(): void
    {
        $user = $this->makeUser();
        Sanctum::actingAs($user, ['*'], 'sanctum');

        $response = $this->getJson('/api/me');
        $response->assertOk();
        $this->assertArrayNotHasKey('password', $response->json());
    }
}