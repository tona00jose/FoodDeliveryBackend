<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
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
            'user_id' => $this->user_id,
            'user_name' => $this->user->name,
            'restaurant_id' => $this->restaurant_id,
            'restaurant_name' => $this->restaurant->name,
            // 'restaurant_description' => $this->restaurant->description,
            'coupon_id' => $this->coupon_id,
            'tip_amount' => $this->tip_amount,
            'subtotal' => $this->subtotal,
            'discount_amount' => $this->discount_amount,
            'total_amount' => $this->total_amount,
            'status' => $this->status,
            'status_name' => $this->status_name,
            'ordered_at' => $this->ordered_at,
            'order_items' => $this->whenLoaded('orderItems'),
            'order_status_histories' => $this->whenLoaded('orderStatusHistories'),
            'created_at' => $this->created_at,
            'deleted_at' => $this->deleted_at
        ];
    }
}
