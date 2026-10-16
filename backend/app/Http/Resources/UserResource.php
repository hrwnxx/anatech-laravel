<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
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
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'status' => $this->status,
            'avatar' => $this->avatar,
            'has_google_login' => $this->hasGoogleLogin(),
            'has_password' => $this->hasPassword(),
            'last_login_at' => $this->last_login_at,
            'created_at' => $this->created_at,
            'purchases_count' => $this->whenCounted('purchases'),
            'reviews_count' => $this->whenCounted('reviews'),
        ];
    }
}
