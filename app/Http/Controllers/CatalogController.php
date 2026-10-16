<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Gadget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function index(Request $request): View
    {
        $gadgets = Gadget::active()
            ->withStats()
            ->search($request->query('keyword'))
            ->when(
                $request->filled('category_id'),
                fn (Builder $query) => $query->where('category_id', $request->query('category_id'))
            )
            ->when(
                $request->filled('brand'),
                fn (Builder $query) => $query->where('brand', $request->query('brand'))
            );

        match ($request->query('sort')) {
            'price_asc' => $gadgets->orderBy('price'),
            'price_desc' => $gadgets->orderByDesc('price'),
            'rating' => $gadgets->orderByDesc('avg_rating'),
            'popular' => $gadgets->orderByDesc('units_sold'),
            'name' => $gadgets->orderBy('gadget_name'),
            default => $gadgets->orderByDesc('id'),
        };

        return view('catalog.index', [
            'gadgets' => $gadgets->get(),
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'brands' => Gadget::active()->distinct()->orderBy('brand')->pluck('brand'),
        ]);
    }

    public function show(Request $request, int $id): View
    {
        $gadget = Gadget::active()->withStats()->findOrFail($id);
        $reviews = $gadget->reviews()->approved()->with('user:id,name')->latest('id')->get();
        $ratingCounts = collect([5, 4, 3, 2, 1])
            ->mapWithKeys(fn (int $star) => [$star => $reviews->where('rating', $star)->count()]);
        $filterRating = (int) $request->query('review_rating', 0);
        $visibleReviews = in_array($filterRating, [1, 2, 3, 4, 5], true)
            ? $reviews->where('rating', $filterRating)
            : $reviews;
        $user = $request->user();

        return view('catalog.show', [
            'gadget' => $gadget,
            'reviews' => $reviews,
            'visibleReviews' => $visibleReviews,
            'ratingCounts' => $ratingCounts,
            'filterRating' => $filterRating,
            'myReview' => $user?->reviews()->where('gadget_id', $gadget->id)->first(),
            'hasPurchased' => $user?->hasPurchased($gadget->id) ?? false,
        ]);
    }
}
