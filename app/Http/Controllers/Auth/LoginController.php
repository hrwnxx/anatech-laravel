<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if ($user = $request->user()) {
            return redirect($user->isAdmin() ? '/admin' : '/');
        }

        $googleError = $request->query('error');

        return view('auth.login', [
            'googleError' => is_string($googleError) ? [
                'google-not-configured' => 'Google login is not set up yet. Please use your email and password.',
                'google-failed' => 'Google login was cancelled or failed. Please try again.',
                'inactive' => 'This account is inactive. Please contact the store.',
            ][$googleError] ?? null : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('web')->attempt([...$credentials, 'status' => 'active'], $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'Invalid email or password.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();
        $user = $request->user();

        return redirect()->intended($user->isAdmin() ? '/admin' : '/');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('catalog.index');
    }
}
