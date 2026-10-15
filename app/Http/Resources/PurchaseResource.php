<?php

namespace App\Http\Resources;

use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Purchase
 */
class PurchaseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'quantity' => $this->quantity,
            'unit_price' => (float) $this->unit_price,
            'total' => round($this->quantity * (float) $this->unit_price, 2),
            'purchase_date' => $this->purchase_date->toDateString(),
            'archived' => $this->archived,
            'customer' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ]),
            'gadget' => $this->whenLoaded('gadget', fn () => [
                'id' => $this->gadget->id,
                'brand' => $this->gadget->brand,
                'gadget_name' => $this->gadget->gadget_name,
                'image_url' => $this->gadget->image_url,
            ]),
        ];
    }
}
