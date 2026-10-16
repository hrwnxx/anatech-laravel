<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Email + password login. Customers and staff use separate pages and
 * endpoints, and each endpoint only accepts its own role. (Customers with a
 * Gmail address log in with Google instead; see Auth\GoogleController.)
 * The React app stays logged in through Laravel's normal session cookie
 * (Sanctum SPA auth).
 */
class AuthController extends Controller
{
    /**
     * For customers without Gmail, using the temporary password staff gave them.
     */
    public function login(Request $request): UserResource
    {
        return new UserResource($this->logInWithPassword($request, 'customer'));
    }

    /**
     * For staff. Its page is not linked anywhere on the public website.
     */
    public function staffLogin(Request $request): UserResource
    {
        return new UserResource($this->logInWithPassword($request, 'admin'));
    }

    /**
     * The logged-in user. React calls this on page load to know who is logged in.
     */
    public function user(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    public function logout(Request $request): Response
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }

    private function logInWithPassword(Request $request, string $role): User
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // A wrong password, an inactive account, an account of the other role,
        // and a Google-only account (no password) all get the same message,
        // so the form never reveals which accounts exist.
        $attempt = [...$credentials, 'role' => $role, 'status' => 'active'];

        if (! Auth::guard('web')->attempt($attempt, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'Invalid email or password.',
            ]);
        }

        $request->session()->regenerate();

        $user = Auth::guard('web')->user();
        $user->recordLogin();

        return $user;
    }
}
