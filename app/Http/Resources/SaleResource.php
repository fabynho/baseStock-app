<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleResource extends JsonResource
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
            'customer_name' => $this->customer_name,
            'delivery_type' => ['value' => $this->delivery_type->value, 'label' => $this->delivery_type->label()],
            'status' => ['value' => $this->status->value, 'label' => $this->status->label()],
            'sold_at' => $this->sold_at?->toIso8601String(),
            'delivered_at' => $this->delivered_at?->toIso8601String(),
            'notes' => $this->notes,
            'seller' => SellerResource::make($this->whenLoaded('seller')),
            'deliverer' => DelivererResource::make($this->whenLoaded('deliverer')),
            'creator' => $this->whenLoaded('creator', fn () => ['id' => $this->creator->id, 'name' => $this->creator->name]),
            'items' => SaleItemResource::collection($this->whenLoaded('items')),
            // Flags prontas para a UI (a autorização real continua no backend)
            'can_confirm' => $this->isPending(),
            'can_cancel' => $this->isPending() && ($request->user()?->isAdmin() ?? false),
        ];
    }
}
