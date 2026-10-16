<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * A customer writing, editing or deleting their own review.
 */
class ReviewController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'gadget_id' => ['required', 'integer', Rule::exists('gadgets', 'id')->where('archived', false)],
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['required', 'string', 'max:1000'],
        ]);

        $user = $request->user();

        // Checked here on the server, not just by hiding the form, so a
        // forged request can't get around it.
        if (! $user->hasPurchased($data['gadget_id'])) {
            abort(403, "You can only review gadgets you've purchased.");
        }

        if ($user->reviews()->where('gadget_id', $data['gadget_id'])->exists()) {
            throw ValidationException::withMessages([
                'gadget_id' => "You've already reviewed this gadget. Edit your existing review instead.",
            ]);
        }

        // New reviews wait for an admin to approve them before they're public.
        $review = $user->reviews()->create([...$data, 'status' => 'pending']);

        return (new ReviewResource($review))->response()->setStatusCode(201);
    }

    public function update(Request $request, Review $review): ReviewResource
    {
        Gate::authorize('update', $review);

        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['required', 'string', 'max:1000'],
        ]);

        // An edited review goes back through moderation, just like a new one.
        $review->update([...$data, 'status' => 'pending']);

        return new ReviewResource($review);
    }

    public function destroy(Review $review): Response
    {
        Gate::authorize('delete', $review);

        $review->delete();

        return response()->noContent();
    }
}
