<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'document_number' => $this->document_number,
            'supplier_name' => $this->supplier_name,
            'document_date' => $this->document_date?->toDateString(),
            'received_at' => $this->received_at?->toIso8601String(),
            'notes' => $this->notes,
            'creator' => $this->whenLoaded('creator', fn () => ['id' => $this->creator->id, 'name' => $this->creator->name]),
            'items' => PurchaseItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
