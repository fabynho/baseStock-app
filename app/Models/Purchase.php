<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Purchase extends Model
{
    protected $fillable = [
        'document_number',
        'supplier_name',
        'document_date',
        'received_at',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'document_date' => 'date',
            'received_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (!$term) {
            return $query;
        }

        return $query->where(fn (Builder $q) => $q->where('document_number', 'like', "%{$term}%")
            ->orWhere('supplier_name', 'like', "%{$term}%"));
    }
}
