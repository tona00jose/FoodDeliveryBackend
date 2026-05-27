<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
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
            'order_id' => $this->order_id,
            'meal_id' => $this->meal_id,
            'quantity' => $this->quantity,
            'price_at_order_time' => $this->price_at_order_time,
            'subtotal' => $this->subtotal,
            'created_at' => $this->created_at,
            'deleted_at' => $this->deleted_at
        ];
    }
}
