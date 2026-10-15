<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_log_in_and_see_who_they_are(): void
    {
        $user = User::factory()->create(['email' => 'juan@example.com']);

        $this->postJson('/api/login', ['email' => 'juan@example.com', 'password' => 'password'])
            ->assertOk()
            ->assertJsonPath('id', $user->id)
            ->assertJsonPath('role', 'customer')
            ->assertJsonMissingPath('password');

        $this->getJson('/api/user')->assertOk()->assertJsonPath('email', 'juan@example.com');
    }

    public function test_wrong_password_is_rejected(): void
    {
        User::factory()->create(['email' => 'juan@example.com']);

        $this->postJson('/api/login', ['email' => 'juan@example.com', 'password' => 'wrong'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email' => 'Invalid email or password.']);

        $this->assertGuest();
    }

    public function test_inactive_account_cannot_log_in(): void
    {
        User::factory()->inactive()->create(['email' => 'juan@example.com']);

        $this->postJson('/api/login', ['email' => 'juan@example.com', 'password' => 'password'])
            ->assertUnprocessable();

        $this->assertGuest();
    }

    public function test_guest_cannot_see_the_current_user(): void
    {
        $this->getJson('/api/user')->assertUnauthorized();
    }

    public function test_user_can_log_out(): void
    {
        $this->actingAs(User::factory()->create());

        $this->postJson('/api/logout')->assertNoContent();

        $this->assertGuest('web');
    }

    public function test_google_login_explains_when_it_is_not_configured(): void
    {
        config(['services.google.client_id' => null]);

        $this->get('/auth/google/redirect')->assertRedirect('/login?error=google-not-configured');
    }
}
