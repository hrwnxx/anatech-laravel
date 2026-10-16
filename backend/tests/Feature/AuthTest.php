<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
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

    public function test_staff_log_in_on_the_staff_login(): void
    {
        $this->freezeSecond();
        $admin = User::factory()->admin()->create(['email' => 'owner@anatech.test']);

        $this->postJson('/api/staff/login', ['email' => 'owner@anatech.test', 'password' => 'password'])
            ->assertOk()
            ->assertJsonPath('role', 'admin');

        $this->assertAuthenticatedAs($admin);
        $this->assertTrue($admin->fresh()->last_login_at->equalTo(now()));
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function wrongLoginPageForRole(): array
    {
        return [
            'staff on the customer login' => ['/api/login', 'admin'],
            'customer on the staff login' => ['/api/staff/login', 'customer'],
        ];
    }

    #[DataProvider('wrongLoginPageForRole')]
    public function test_each_login_only_accepts_its_own_role(string $url, string $role): void
    {
        User::factory()->create(['email' => 'someone@example.com', 'role' => $role]);

        $this->postJson($url, ['email' => 'someone@example.com', 'password' => 'password'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email' => 'Invalid email or password.']);

        $this->assertGuest();
    }

    public function test_customer_without_a_password_cannot_use_the_password_login(): void
    {
        User::factory()->withoutPassword()->create(['email' => 'maria@gmail.com']);

        $this->postJson('/api/login', ['email' => 'maria@gmail.com', 'password' => 'anything'])
            ->assertUnprocessable();

        $this->assertGuest();
    }

    public function test_password_login_is_rate_limited(): void
    {
        User::factory()->create(['email' => 'juan@example.com']);

        for ($attempt = 1; $attempt <= 10; $attempt++) {
            $this->postJson('/api/login', ['email' => 'juan@example.com', 'password' => 'wrong'])->assertUnprocessable();
        }

        $this->postJson('/api/login', ['email' => 'juan@example.com', 'password' => 'password'])->assertTooManyRequests();
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

        $this->get('/auth/google/redirect')
            ->assertRedirect(config('app.frontend_url').'/login?error=google-not-configured');
    }
}
