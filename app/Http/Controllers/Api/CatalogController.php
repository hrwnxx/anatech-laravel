<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\GadgetResource;
use App\Http\Resources\ReviewResource;
use App\Models\Category;
use App\Models\Gadget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * The public catalog. Anyone can browse; no login needed.
 */
class CatalogController extends Controller
{
    /**
     * Active gadgets, filtered by ?keyword, ?category_id and ?brand,
     * sorted by ?sort (newest, price_asc, price_desc, rating, popular, name).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Gadget::active()
            ->withStats()
            ->search($request->query('keyword'))
            ->when($request->filled('category_id'), fn (Builder $q) => $q->where('category_id', $request->query('category_id')))
            ->when($request->filled('brand'), fn (Builder $q) => $q->where('brand', $request->query('brand')));

        match ($request->query('sort')) {
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            'rating' => $query->orderByDesc('avg_rating'),
            'popular' => $query->orderByDesc('units_sold'),
            'name' => $query->orderBy('gadget_name'),
            default => $query->orderByDesc('id'),
        };

        return GadgetResource::collection($query->get());
    }

    /**
     * One gadget's page: details, approved reviews, star counts, and what
     * the current visitor is allowed to do (e.g. write a review).
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $gadget = Gadget::active()->withStats()->findOrFail($id);

        $reviews = $gadget->reviews()->approved()->with('user:id,name')->latest('id')->get();

        $user = $request->user();
        $myReview = $user?->reviews()->where('gadget_id', $gadget->id)->first();

        return response()->json([
            'gadget' => new GadgetResource($gadget),
            'reviews' => ReviewResource::collection($reviews),
            // How many approved reviews gave 5 stars, 4 stars, ... (for the filter buttons).
            'rating_counts' => collect([5, 4, 3, 2, 1])
                ->mapWithKeys(fn (int $star) => [$star => $reviews->where('rating', $star)->count()]),
            'viewer' => [
                'logged_in' => $user !== null,
                'has_purchased' => $user?->hasPurchased($gadget->id) ?? false,
                'my_review' => $myReview ? new ReviewResource($myReview) : null,
            ],
        ]);
    }

    /**
     * Categories for the filter dropdowns.
     */
    public function categories(): JsonResponse
    {
        return response()->json(Category::orderBy('name')->get(['id', 'name']));
    }

    /**
     * Brands of active gadgets, for the "Brand" filter dropdown.
     */
    public function brands(): JsonResponse
    {
        return response()->json(Gadget::active()->distinct()->orderBy('brand')->pluck('brand'));
    }
}
