<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Exceptions;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as GoogleUser;
use RuntimeException;
use Tests\TestCase;

/**
 * Google is a way for customers to log in, never to sign up: accounts are
 * created by staff when they record an in-store purchase.
 */
class GoogleLoginTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_customer_created_at_the_store_logs_in_and_their_google_account_is_linked(): void
    {
        $this->freezeSecond();
        $customer = User::factory()->withoutPassword()->create(['email' => 'maria@gmail.com']);
        $this->fakeGoogleUser(['id' => 'google-123', 'email' => 'maria@gmail.com']);

        $this->get('/auth/google/callback')->assertRedirect($this->frontendUrl('/'));

        $this->assertAuthenticatedAs($customer);
        $customer->refresh();
        $this->assertSame('google-123', $customer->google_id);
        $this->assertTrue($customer->last_login_at->equalTo(now()));
    }

    public function test_google_email_matches_regardless_of_capital_letters(): void
    {
        $customer = User::factory()->create(['email' => 'maria@gmail.com']);
        $this->fakeGoogleUser(['email' => 'Maria@Gmail.com']);

        $this->get('/auth/google/callback');

        $this->assertAuthenticatedAs($customer);
    }

    public function test_linked_customer_can_still_log_in_after_their_gmail_address_changes(): void
    {
        $customer = User::factory()->create(['email' => 'old@gmail.com', 'google_id' => 'google-123']);
        $this->fakeGoogleUser(['id' => 'google-123', 'email' => 'new@gmail.com']);

        $this->get('/auth/google/callback');

        $this->assertAuthenticatedAs($customer);
    }

    public function test_google_login_never_creates_an_account(): void
    {
        $this->fakeGoogleUser(['email' => 'stranger@gmail.com']);

        $this->get('/auth/google/callback')->assertRedirect($this->frontendUrl('/login?error=no-account'));

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'stranger@gmail.com']);
    }

    public function test_staff_cannot_log_in_with_google(): void
    {
        User::factory()->admin()->create(['email' => 'owner@gmail.com']);
        $this->fakeGoogleUser(['email' => 'owner@gmail.com']);

        $this->get('/auth/google/callback')->assertRedirect($this->frontendUrl('/login?error=no-account'));

        $this->assertGuest();
    }

    public function test_email_linked_to_a_different_google_account_cannot_be_taken_over(): void
    {
        User::factory()->create(['email' => 'maria@gmail.com', 'google_id' => 'google-999']);
        $this->fakeGoogleUser(['id' => 'google-123', 'email' => 'maria@gmail.com']);

        $this->get('/auth/google/callback')->assertRedirect($this->frontendUrl('/login?error=no-account'));

        $this->assertGuest();
    }

    public function test_inactive_customer_is_refused(): void
    {
        User::factory()->inactive()->create(['email' => 'maria@gmail.com']);
        $this->fakeGoogleUser(['email' => 'maria@gmail.com']);

        $this->get('/auth/google/callback')->assertRedirect($this->frontendUrl('/login?error=inactive'));

        $this->assertGuest();
    }

    public function test_google_account_with_an_unverified_email_is_refused(): void
    {
        User::factory()->create(['email' => 'maria@gmail.com']);
        $this->fakeGoogleUser(['email' => 'maria@gmail.com', 'email_verified' => false]);

        $this->get('/auth/google/callback')->assertRedirect($this->frontendUrl('/login?error=google-unverified'));

        $this->assertGuest();
    }

    public function test_google_account_without_an_email_is_refused(): void
    {
        $this->fakeGoogleUser(['email' => null]);

        $this->get('/auth/google/callback')->assertRedirect($this->frontendUrl('/login?error=google-unverified'));

        $this->assertGuest();
        $this->assertSame(0, User::count());
    }

    public function test_failed_google_login_returns_to_the_login_page(): void
    {
        Exceptions::fake();
        Socialite::fake('google', fn () => throw new RuntimeException('Invalid state'));

        $this->get('/auth/google/callback')->assertRedirect($this->frontendUrl('/login?error=google-failed'));

        $this->assertGuest();
        Exceptions::assertReported(RuntimeException::class);
    }

    public function test_cancelling_on_googles_page_returns_to_the_login_page(): void
    {
        $this->get('/auth/google/callback?error=access_denied')
            ->assertRedirect($this->frontendUrl('/login?error=google-cancelled'));

        $this->assertGuest();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function fakeGoogleUser(array $attributes): void
    {
        Socialite::fake('google', GoogleUser::fake([
            'email_verified' => true,
            ...$attributes,
        ]));
    }

    private function frontendUrl(string $path): string
    {
        return config('app.frontend_url').$path;
    }
}
