<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\GadgetResource;
use App\Models\Gadget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Admin product management: add/edit gadgets, archive them, and manage stock.
 */
class GadgetController extends Controller
{
    /**
     * Active gadgets, or archived ones with ?archived=1. Filter with ?keyword and ?category_id.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $gadgets = Gadget::withStats()
            ->where('archived', $request->boolean('archived'))
            ->search($request->query('keyword'))
            ->when($request->filled('category_id'), fn (Builder $q) => $q->where('category_id', $request->query('category_id')))
            ->orderBy('gadget_name')
            ->get();

        return GadgetResource::collection($gadgets);
    }

    public function show(int $id): GadgetResource
    {
        return new GadgetResource(Gadget::withStats()->findOrFail($id));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validateGadget($request);
        $data['image'] = $request->file('image')?->store('gadgets', 'public');

        $gadget = DB::transaction(function () use ($data) {
            $gadget = Gadget::create($data);

            // Every gadget gets an inventory row. New gadgets start at 0;
            // use Stock In to add units.
            $gadget->inventory()->create(['quantity' => 0]);

            return $gadget;
        });

        return (new GadgetResource(Gadget::withStats()->find($gadget->id)))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Registered as POST (not PUT) because PHP only reads uploaded files on POST requests.
     */
    public function update(Request $request, Gadget $gadget): GadgetResource
    {
        $data = $this->validateGadget($request);
        unset($data['image']);

        // Only replace the photo when a new one was uploaded.
        if ($request->hasFile('image')) {
            $oldImage = $gadget->image;
            $data['image'] = $request->file('image')->store('gadgets', 'public');

            if ($oldImage) {
                Storage::disk('public')->delete($oldImage);
            }
        }

        $gadget->update($data);

        return new GadgetResource(Gadget::withStats()->find($gadget->id));
    }

    /**
     * Hides the gadget from the catalog instead of deleting it, so past
     * purchases and reviews still point at a real product.
     */
    public function archive(Gadget $gadget): GadgetResource
    {
        $gadget->update(['archived' => true]);

        return new GadgetResource(Gadget::withStats()->find($gadget->id));
    }

    public function unarchive(Gadget $gadget): GadgetResource
    {
        $gadget->update(['archived' => false]);

        return new GadgetResource(Gadget::withStats()->find($gadget->id));
    }

    public function stockIn(Request $request, Gadget $gadget): JsonResponse
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        return response()->json(['stock' => $gadget->addStock($data['quantity'])]);
    }

    /**
     * Removes units (damage, count corrections, ...). Refuses to go below zero.
     */
    public function stockOut(Request $request, Gadget $gadget): JsonResponse
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $stock = DB::transaction(fn () => $gadget->removeStock($data['quantity']));

        return response()->json(['stock' => $stock]);
    }

    private function validateGadget(Request $request): array
    {
        return $request->validate([
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'brand' => ['required', 'string', 'max:50'],
            'gadget_name' => ['required', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'description' => ['nullable', 'string', 'max:5000'],
            'image' => ['nullable', 'image', 'max:4096'],
        ]);
    }
}
