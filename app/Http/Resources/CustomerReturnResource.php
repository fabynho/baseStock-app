<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerReturnResource extends JsonResource
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
            'document_number' => $this->document_number,
            'sale_id' => $this->sale_id,
            'customer_name' => $this->customer_name,
            'reason' => $this->reason,
            'returned_at' => $this->returned_at?->toIso8601String(),
            'creator' => $this->whenLoaded('creator', fn () => ['id' => $this->creator->id, 'name' => $this->creator->name]),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'floor_id' => $item->floor_id,
                'quantity_m2' => $item->quantity_m2,
                'floor' => $item->relationLoaded('floor') ? [
                    'id' => $item->floor->id,
                    'code' => $item->floor->code,
                    'description' => $item->floor->description,
                    'reference' => $item->floor->reference,
                ] : null,
            ])->values()->all()),
        ];
    }
}
