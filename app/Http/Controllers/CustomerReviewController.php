<?php

namespace App\Http\Controllers;

use App\Models\Gadget;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CustomerReviewController extends Controller
{
    public function store(Request $request, int $id): RedirectResponse
    {
        $gadget = Gadget::active()->findOrFail($id);
        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['required', 'string', 'max:1000'],
        ]);
        $user = $request->user();

        if (! $user->hasPurchased($gadget->id)) {
            abort(403, "You can only review gadgets you've purchased.");
        }

        if ($user->reviews()->where('gadget_id', $gadget->id)->exists()) {
            throw ValidationException::withMessages([
                'rating' => "You've already reviewed this gadget. Edit your existing review instead.",
            ]);
        }

        $user->reviews()->create([...$data, 'gadget_id' => $gadget->id, 'status' => 'pending']);

        return redirect()->route('catalog.show', $gadget->id)
            ->with('status', 'Thanks! Your review was sent and will appear once an admin approves it.');
    }

    public function update(Request $request, Review $review): RedirectResponse
    {
        Gate::authorize('update', $review);

        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['required', 'string', 'max:1000'],
        ]);

        $review->update([...$data, 'status' => 'pending']);

        return back()->with('status', 'Your review was updated and is waiting for approval again.');
    }

    public function destroy(Review $review): RedirectResponse
    {
        Gate::authorize('delete', $review);
        $review->delete();

        return back()->with('status', 'Your review was deleted.');
    }
}
