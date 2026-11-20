<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockMovementResource extends JsonResource
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
            'type' => ['value' => $this->type->value, 'label' => $this->type->label()],
            'quantity_m2' => $this->quantity_m2,
            'physical_balance_after' => $this->physical_balance_after,
            'committed_balance_after' => $this->committed_balance_after,
            'document_number' => $this->document_number,
            'sale_id' => $this->sale_id,
            'purchase_id' => $this->purchase_id,
            'reason' => $this->reason,
            'created_at' => $this->created_at?->toIso8601String(),
            'floor' => $this->whenLoaded('floor', fn () => [
                'id' => $this->floor->id,
                'code' => $this->floor->code,
                'description' => $this->floor->description,
                'reference' => $this->floor->reference,
            ]),
            'user' => $this->whenLoaded('user', fn () => ['id' => $this->user->id, 'name' => $this->user->name]),
        ];
    }
}
