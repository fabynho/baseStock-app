<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Versão enxuta para selects/filtros (sem saldos). */
class FloorOptionResource extends JsonResource
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
        ];
    }
}
