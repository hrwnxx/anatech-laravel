<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PurchaseResource;
use App\Http\Resources\ReviewResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * The logged-in customer's own "My Purchases" and "My Reviews" pages.
 */
class MyAccountController extends Controller
{
    public function purchases(Request $request): AnonymousResourceCollection
    {
        $purchases = $request->user()->purchases()
            ->active()
            ->with('gadget')
            ->latest('purchase_date')
            ->get();

        return PurchaseResource::collection($purchases);
    }

    /**
     * Every review the customer wrote, in any status (pending, approved, rejected).
     */
    public function reviews(Request $request): AnonymousResourceCollection
    {
        $reviews = $request->user()->reviews()
            ->with('gadget')
            ->latest('id')
            ->get();

        return ReviewResource::collection($reviews);
    }
}
