<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Deliverer extends Model
{
    protected $fillable = ['name', 'vehicle_info', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    protected function name(): Attribute
    {
        return Attribute::set(fn (?string $value): ?string => is_string($value) && filled(trim($value))
            ? Str::upper(trim($value), 'UTF-8')
            : $value);
    }

    protected function vehicleInfo(): Attribute
    {
        return Attribute::set(fn (?string $value): ?string => is_string($value) && filled(trim($value))
            ? Str::upper(trim($value), 'UTF-8')
            : $value);
    }
}
