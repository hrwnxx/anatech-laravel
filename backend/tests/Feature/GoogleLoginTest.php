<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as GoogleUser;
use Tests\TestCase;

class GoogleLoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Pretends Google answered the callback with this account.
     *
     * @param  array<string, string|null>  $profile
     */
    private function googleReturns(array $profile): void
    {
        $googleUser = (new GoogleUser)->map($profile);

        Socialite::shouldReceive('driver->user')->andReturn($googleUser);
    }

    public function test_redirect_sends_the_browser_to_google_when_it_is_configured(): void
    {
        config([
            'services.google.client_id' => 'test-client-id',
            'services.google.client_secret' => 'test-secret',
            'services.google.redirect' => 'http://localhost:8000/auth/google/callback',
        ]);

        $response = $this->get('/auth/google/redirect');

        $response->assertRedirectContains('https://accounts.google.com/o/oauth2/auth');
        $response->assertRedirectContains('prompt=select_account');
        $response->assertRedirectContains(urlencode('http://localhost:8000/auth/google/callback'));
    }

    public function test_first_google_login_creates_a_customer_account_and_logs_in(): void
    {
        $this->googleReturns([
            'id' => 'g-123',
            'name' => 'Maria Santos',
            'email' => 'maria@gmail.com',
            'avatar' => 'https://example.com/maria.png',
        ]);

        $this->get('/auth/google/callback')->assertRedirect(config('app.frontend_url').'/');

        $user = User::where('email', 'maria@gmail.com')->firstOrFail();
        $this->assertSame('customer', $user->role);
        $this->assertSame('active', $user->status);
        $this->assertSame('g-123', $user->google_id);
        $this->assertAuthenticatedAs($user);
    }

    public function test_google_login_with_an_existing_email_uses_that_account(): void
    {
        $existing = User::factory()->create(['email' => 'juan@example.com', 'role' => 'customer']);

        $this->googleReturns(['id' => 'g-999', 'name' => 'Juan', 'email' => 'juan@example.com', 'avatar' => null]);

        $this->get('/auth/google/callback')->assertRedirect(config('app.frontend_url').'/');

        $this->assertSame(1, User::where('email', 'juan@example.com')->count());
        $this->assertSame('g-999', $existing->fresh()->google_id);
        $this->assertAuthenticatedAs($existing);
    }

    public function test_google_login_sends_admins_to_the_admin_area(): void
    {
        $admin = User::factory()->admin()->create(['email' => 'admin@gadgetshop.com']);

        $this->googleReturns(['id' => 'g-1', 'name' => 'Admin', 'email' => 'admin@gadgetshop.com', 'avatar' => null]);

        $this->get('/auth/google/callback')->assertRedirect(config('app.frontend_url').'/admin');

        $this->assertAuthenticatedAs($admin);
    }

    public function test_inactive_accounts_cannot_log_in_with_google(): void
    {
        User::factory()->inactive()->create(['email' => 'old@example.com']);

        $this->googleReturns(['id' => 'g-2', 'name' => 'Old', 'email' => 'old@example.com', 'avatar' => null]);

        $this->get('/auth/google/callback')
            ->assertRedirect(config('app.frontend_url').'/login?error=inactive');

        $this->assertGuest();
    }

    public function test_google_account_without_an_email_is_rejected(): void
    {
        $this->googleReturns(['id' => 'g-3', 'name' => 'No Email', 'email' => null, 'avatar' => null]);

        $this->get('/auth/google/callback')
            ->assertRedirect(config('app.frontend_url').'/login?error=google-failed');

        $this->assertGuest();
        $this->assertSame(0, User::count());
    }

    public function test_cancelled_or_failed_google_login_returns_to_the_login_page(): void
    {
        Socialite::shouldReceive('driver->user')->andThrow(new \RuntimeException('access_denied'));

        $this->get('/auth/google/callback')
            ->assertRedirect(config('app.frontend_url').'/login?error=google-failed');

        $this->assertGuest();
    }
}
