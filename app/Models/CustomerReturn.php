<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class CustomerReturn extends Model
{
    protected $fillable = [
        'document_number', 'sale_id', 'customer_name', 'reason', 'returned_at', 'created_by',
    ];

    protected function casts(): array
    {
        return ['returned_at' => 'datetime'];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CustomerReturnItem::class);
    }

    protected function customerName(): Attribute
    {
        return Attribute::set(fn (?string $value): ?string => filled($value) ? Str::upper(trim($value), 'UTF-8') : null);
    }
}
