<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\PurchaseResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * The admin Customers page: every customer and how active they've been.
 */
class CustomerController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $customers = User::where('role', 'customer')
            ->search($request->query('keyword'))
            ->withCount([
                'purchases' => fn (Builder $q) => $q->where('archived', false),
                'reviews',
            ])
            ->orderBy('name')
            ->get();

        return UserResource::collection($customers);
    }

    /**
     * One customer's purchase history (the "View purchases" popup).
     */
    public function purchases(User $user): AnonymousResourceCollection
    {
        abort_unless($user->role === 'customer', 404);

        $purchases = $user->purchases()
            ->active()
            ->with('gadget:id,brand,gadget_name,image')
            ->latest('purchase_date')
            ->get();

        return PurchaseResource::collection($purchases);
    }
}
