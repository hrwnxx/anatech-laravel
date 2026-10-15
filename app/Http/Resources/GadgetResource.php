<?php

namespace App\Http\Resources;

use App\Models\Gadget;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Shapes a Gadget into the JSON the React pages receive.
 *
 * @mixin Gadget
 */
class GadgetResource extends JsonResource
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
            'category_id' => $this->category_id,
            'category' => $this->whenLoaded('category', fn () => $this->category->name),
            'brand' => $this->brand,
            'gadget_name' => $this->gadget_name,
            'model' => $this->model,
            'price' => (float) $this->price,
            'description' => $this->description,
            'image_url' => $this->image_url,
            'archived' => $this->archived,
            // These four come from the Gadget::withStats() scope.
            'stock' => (int) $this->stock,
            'units_sold' => (int) $this->units_sold,
            'avg_rating' => round((float) $this->avg_rating, 1),
            'review_count' => (int) $this->review_count,
            'created_at' => $this->created_at,
        ];
    }
}
