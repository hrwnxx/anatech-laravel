<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\PurchaseResource;
use App\Models\Gadget;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Recording in-store sales. A recorded purchase is what allows a customer
 * to review that gadget.
 */
class PurchaseController extends Controller
{
    /**
     * Purchase history, or archived records with ?archived=1.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $purchases = Purchase::with(['user:id,name,email', 'gadget:id,brand,gadget_name,image'])
            ->where('archived', $request->boolean('archived'))
            ->latest('id')
            ->get();

        return PurchaseResource::collection($purchases);
    }

    /**
     * Records one sale. With "new_customer": true, the customer's account is
     * created in the same step (customers don't sign up on their own).
     * The email should be the customer's Gmail address, which is how they log
     * in with Google. The temporary password is optional and only needed for
     * customers without Gmail.
     */
    public function store(Request $request): JsonResponse
    {
        $isNewCustomer = $request->boolean('new_customer');

        $data = $request->validate([
            'user_id' => [Rule::excludeIf($isNewCustomer), 'required', Rule::exists('users', 'id')->where('role', 'customer')],
            'name' => [Rule::excludeIf(! $isNewCustomer), 'required', 'string', 'max:100'],
            'email' => [Rule::excludeIf(! $isNewCustomer), 'required', 'email', 'max:150', 'unique:users,email'],
            'password' => [Rule::excludeIf(! $isNewCustomer), 'nullable', 'string', 'min:6'],
            'gadget_id' => ['required', 'integer', Rule::exists('gadgets', 'id')->where('archived', false)],
            'quantity' => ['required', 'integer', 'min:1'],
            'purchase_date' => ['required', 'date', 'before_or_equal:today'],
        ]);

        // Everything below happens together or not at all: if the stock
        // check fails, the new customer account is rolled back too.
        $purchase = DB::transaction(function () use ($data, $isNewCustomer) {
            $userId = $isNewCustomer
                ? User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => $data['password'] ?? null,
                    'role' => 'customer',
                    'status' => 'active',
                ])->id
                : $data['user_id'];

            $gadget = Gadget::findOrFail($data['gadget_id']);
            $gadget->removeStock($data['quantity']);

            return Purchase::create([
                'user_id' => $userId,
                'gadget_id' => $gadget->id,
                'quantity' => $data['quantity'],
                'unit_price' => $gadget->price,
                'purchase_date' => $data['purchase_date'],
            ]);
        });

        return (new PurchaseResource($purchase->load('user', 'gadget')))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Hides the record from the active list without deleting it, so the
     * customer can still review what they bought.
     */
    public function archive(Purchase $purchase): PurchaseResource
    {
        $purchase->update(['archived' => true]);

        return new PurchaseResource($purchase->load('user', 'gadget'));
    }

    public function unarchive(Purchase $purchase): PurchaseResource
    {
        $purchase->update(['archived' => false]);

        return new PurchaseResource($purchase->load('user', 'gadget'));
    }
}
