<?php

namespace App\Models;

use App\Enums\DeliveryType;
use App\Enums\SaleStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Sale extends Model
{
    protected $fillable = [
        'document_number',
        'customer_name',
        'seller_id',
        'deliverer_id',
        'delivery_type',
        'status',
        'sold_at',
        'delivered_at',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'delivery_type' => DeliveryType::class,
            'status' => SaleStatus::class,
            'sold_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    public function deliverer(): BelongsTo
    {
        return $this->belongsTo(Deliverer::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(CustomerReturn::class);
    }

    public function isPending(): bool
    {
        return $this->status === SaleStatus::PendingDelivery;
    }

    public function isDelivered(): bool
    {
        return $this->status === SaleStatus::Delivered;
    }

    public function scopePendingDelivery(Builder $query): Builder
    {
        return $query->where('status', SaleStatus::PendingDelivery->value);
    }
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        $like = '%'.addcslashes(trim($term), '%_\\').'%';

        return $query->where(fn (Builder $q) => $q
            ->where('document_number', 'like', $like)
            ->orWhere('customer_name', 'like', $like));
    }

    protected function customerName(): Attribute
    {
        return Attribute::set(fn (?string $value): ?string => is_string($value) && filled(trim($value))
            ? Str::upper(trim($value), 'UTF-8')
            : $value);
    }
}
