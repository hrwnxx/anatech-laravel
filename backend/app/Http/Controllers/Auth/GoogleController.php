<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Throwable;

/**
 * "Login with Google" using Laravel Socialite.
 *
 * 1. The Login page links to /auth/google/redirect, which sends the browser to Google.
 * 2. Google sends the browser back to /auth/google/callback with the user's details.
 * 3. We find or create the account, log it in, and send the browser back to React.
 */
class GoogleController extends Controller
{
    public function redirect(): SymfonyRedirectResponse
    {
        if (blank(config('services.google.client_id'))) {
            return redirect($this->frontendUrl('/login?error=google-not-configured'));
        }

        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable) {
            return redirect($this->frontendUrl('/login?error=google-failed'));
        }

        // Use the account already linked to this Google login, or the one
        // with the same email (e.g. created by an admin at the store).
        $user = User::where('google_id', $googleUser->getId())->first()
            ?? User::where('email', $googleUser->getEmail())->first();

        if ($user && ! $user->isActive()) {
            return redirect($this->frontendUrl('/login?error=inactive'));
        }

        if ($user) {
            $user->update([
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
            ]);
        } else {
            // First time with Google and no matching account: create a
            // customer account. They can browse right away, but can only
            // review gadgets once an admin records a purchase for them.
            $user = User::create([
                'name' => $googleUser->getName() ?: $googleUser->getEmail(),
                'email' => $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
                'role' => 'customer',
                'status' => 'active',
            ]);
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect($this->frontendUrl($user->isAdmin() ? '/admin' : '/'));
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
