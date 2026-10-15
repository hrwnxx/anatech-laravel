<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Review moderation: approve or reject what customers wrote.
 */
class ReviewController extends Controller
{
    /**
     * ?status=pending (default) for the moderation queue, or
     * ?status=archived for reviews already approved or rejected.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $statuses = $request->query('status') === 'archived' ? ['approved', 'rejected'] : ['pending'];

        $reviews = Review::with(['user:id,name', 'gadget:id,brand,gadget_name,image'])
            ->whereIn('status', $statuses)
            ->latest('id')
            ->get();

        return ReviewResource::collection($reviews);
    }

    public function approve(Review $review): ReviewResource
    {
        $review->update(['status' => 'approved']);

        return new ReviewResource($review->load('user', 'gadget'));
    }

    /**
     * Rejected reviews are kept (in the Archive tab), never deleted.
     */
    public function reject(Review $review): ReviewResource
    {
        $review->update(['status' => 'rejected']);

        return new ReviewResource($review->load('user', 'gadget'));
    }

    /**
     * Approve or reject many reviews at once (the "select all" checkboxes).
     */
    public function bulk(Request $request): JsonResponse
    {
        $data = $request->validate([
            'action' => ['required', 'in:approve,reject'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:reviews,id'],
        ]);

        $updated = Review::whereIn('id', $data['ids'])->update([
            'status' => $data['action'] === 'approve' ? 'approved' : 'rejected',
        ]);

        return response()->json(['updated' => $updated]);
    }
}
