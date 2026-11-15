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
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => ['value' => $this->role->value, 'label' => $this->role->label()],
            'is_active' => $this->is_active,
            'is_self' => $request->user()?->is($this->resource) ?? false,
        ];
    }
}
