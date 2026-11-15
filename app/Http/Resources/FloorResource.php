<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FloorResource extends JsonResource
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
            'code' => $this->code,
            'reference' => $this->reference,
            'description' => $this->description,
            'brand' => $this->brand,
            'm2_per_box' => $this->m2_per_box,
            'physical_stock' => $this->physical_stock,
            'committed_stock' => $this->committed_stock,
            'available_stock' => $this->available_stock,
            'min_stock_alert' => $this->min_stock_alert,
            'below_minimum' => $this->isBelowMinimum(),
            'is_active' => $this->is_active,
        ];
    }
}
