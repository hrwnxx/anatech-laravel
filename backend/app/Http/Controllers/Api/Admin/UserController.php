<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Admin user management (both admin and customer accounts).
 */
class UserController extends Controller
{
    /**
     * Filter with ?keyword (name or email), ?role and ?status.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $users = User::search($request->query('keyword'))
            ->when($request->filled('role'), fn (Builder $q) => $q->where('role', $request->query('role')))
            ->when($request->filled('status'), fn (Builder $q) => $q->where('status', $request->query('status')))
            ->latest()
            ->get();

        return UserResource::collection($users);
    }

    public function store(Request $request): JsonResponse
    {
        // Staff always log in with a password. Customers usually log in with
        // Google, so their password is optional.
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => [Rule::requiredIf($request->input('role') === 'admin'), 'nullable', 'string', 'min:6'],
            'role' => ['required', 'in:admin,customer'],
            'status' => ['sometimes', 'in:active,inactive'],
        ], [
            'password.required' => 'Staff accounts need a password, because staff log in with a password.',
        ]);

        // The password is hashed automatically by the User model's "hashed" cast.
        $user = User::create(['status' => 'active', ...$data]);

        return (new UserResource($user))->response()->setStatusCode(201);
    }

    public function update(Request $request, User $user): UserResource
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users')->ignore($user)],
            'role' => ['required', 'in:admin,customer'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        // Stops an admin from locking themselves out of the admin panel.
        if ($user->is($request->user()) && ($data['role'] !== 'admin' || $data['status'] !== 'active')) {
            throw ValidationException::withMessages([
                'role' => "You can't remove your own admin access or deactivate your own account.",
            ]);
        }

        $user->fill($data);

        // A changed email (for example, staff fixing a typo) unlinks the old
        // Google account, so only the Google account with the new email can
        // log in from now on.
        if ($user->isDirty('email')) {
            $user->google_id = null;
        }

        $user->save();

        return new UserResource($user);
    }

    public function updatePassword(Request $request, User $user): Response
    {
        $data = $request->validate([
            'password' => ['required', 'string', 'min:6'],
        ]);

        $user->update($data);

        return response()->noContent();
    }

    /**
     * Deleting a user also deletes their purchases and reviews (foreign keys
     * cascade), so setting the account to "inactive" is usually the better choice.
     */
    public function destroy(Request $request, User $user): Response
    {
        if ($user->is($request->user())) {
            throw ValidationException::withMessages([
                'user' => 'You cannot delete your own account.',
            ]);
        }

        $user->delete();

        return response()->noContent();
    }
}
