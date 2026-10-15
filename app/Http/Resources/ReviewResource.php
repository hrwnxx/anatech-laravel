<?php

namespace App\Http\Resources;

use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Review
 */
class ReviewResource extends JsonResource
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
            'gadget_id' => $this->gadget_id,
            'rating' => $this->rating,
            'comment' => $this->comment,
            'status' => $this->status,
            'created_at' => $this->created_at,
            // Admin pages see the real name; public pages only see "Juan C.".
            'reviewer' => $this->whenLoaded('user', fn () => $request->is('api/admin/*')
                ? $this->user->name
                : $this->user->maskedName()),
            // Lets the page show Edit/Delete buttons on the visitor's own review.
            'is_mine' => $request->user()?->id === $this->user_id,
            'gadget' => $this->whenLoaded('gadget', fn () => [
                'id' => $this->gadget->id,
                'brand' => $this->gadget->brand,
                'gadget_name' => $this->gadget->gadget_name,
                'image_url' => $this->gadget->image_url,
            ]),
        ];
    }
}
