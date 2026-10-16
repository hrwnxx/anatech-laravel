<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as GoogleUser;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Throwable;

/**
 * "Continue with Google" for customers, using Laravel Socialite.
 *
 * Google is a way to LOG IN, never to sign up. Customer accounts are only
 * created by staff when they record an in-store purchase, using the
 * customer's Gmail address. Staff never log in with Google; they use the
 * separate staff login page.
 *
 * 1. The customer Login page links to /auth/google/redirect, which sends the browser to Google.
 * 2. Google sends the browser back to /auth/google/callback with the person's details.
 * 3. We find the matching customer account (or refuse), log it in, and send the browser back to React.
 */
class GoogleController extends Controller
{
    public function redirect(): SymfonyRedirectResponse
    {
        if (blank(config('services.google.client_id'))) {
            return redirect($this->frontendUrl('/login?error=google-not-configured'));
        }

        // "select_account" makes Google always show the account chooser, so a
        // different Google account can be used on a shared computer.
        return Socialite::driver('google')->with(['prompt' => 'select_account'])->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        // The person pressed "Cancel" on Google's page.
        if ($request->filled('error')) {
            return redirect($this->frontendUrl('/login?error=google-cancelled'));
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable $exception) {
            // Written to storage/logs/laravel.log so the real reason can be found.
            report($exception);

            return redirect($this->frontendUrl('/login?error=google-failed'));
        }

        if (! $this->hasVerifiedEmail($googleUser)) {
            return redirect($this->frontendUrl('/login?error=google-unverified'));
        }

        $customer = $this->findCustomer($googleUser);

        // No account is ever created here. Staff accounts get the same answer,
        // so the public login page never reveals who the staff are.
        if (! $customer) {
            return redirect($this->frontendUrl('/login?error=no-account'));
        }

        if (! $customer->isActive()) {
            return redirect($this->frontendUrl('/login?error=inactive'));
        }

        // Links the Google account on the first login. Later logins match by
        // google_id, so they keep working even if the Gmail address changes.
        $customer->update([
            'google_id' => $googleUser->getId(),
            'avatar' => $googleUser->getAvatar(),
        ]);

        Auth::login($customer, remember: true);
        $request->session()->regenerate();
        $customer->recordLogin();

        return redirect($this->frontendUrl('/'));
    }

    private function hasVerifiedEmail(GoogleUser $googleUser): bool
    {
        $isVerified = filter_var($googleUser->getRaw()['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN);

        return $isVerified && filled($googleUser->getEmail());
    }

    /**
     * The customer already linked to this Google account, or else the
     * not-yet-linked customer whose email staff entered at the store.
     */
    private function findCustomer(GoogleUser $googleUser): ?User
    {
        return User::where('role', 'customer')->where('google_id', $googleUser->getId())->first()
            ?? User::where('role', 'customer')
                ->whereNull('google_id')
                ->where('email', Str::lower($googleUser->getEmail()))
                ->first();
    }

    /**
     * Full address of a page in the React app. The browser is on the Laravel
     * server at this point, so a bare "/login" would point at the wrong site.
     */
    private function frontendUrl(string $path): string
    {
        return config('app.frontend_url').$path;
    }
}
