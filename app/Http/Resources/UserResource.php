<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // return parent::toArray($request);
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            // 'restaurants' => $this->restaurants,
            'restaurants' => $this->whenLoaded('restaurants'),
            'is_super_admin' => $this->is_super_admin,
            'is_blocked' => $this->is_blocked,
            'created_at' => $this->created_at
        ];
    }
}
