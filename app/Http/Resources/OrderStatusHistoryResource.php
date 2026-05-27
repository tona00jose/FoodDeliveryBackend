<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderStatusHistoryResource extends JsonResource
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
            'old_status' => $this->old_status,
            'new_status' => $this->new_status,
            'changed_by' => $this->changed_by,
            'created_at' => $this->created_at,
            'deleted_at' => $this->deleted_at
        ];
    }
}
