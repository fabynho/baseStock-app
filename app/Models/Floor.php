<?php

namespace App\Models;

use Brick\Math\BigDecimal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Os campos physical_stock e committed_stock NÃO são mass-assignable:
 * só o StockService altera saldos (sempre gerando um StockMovement).
 *
 * @property-read string $available_stock
 */

class Floor extends Model
{
    protected $fillable = [
        'code',
        'reference',
        'description',
        'brand',
        'm2_per_box',
        'min_stock_alert',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'm2_per_box' => 'decimal:4',
            'physical_stock' => 'decimal:4',
            'committed_stock' => 'decimal:4',
            'min_stock_alert' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }

    /** Disponível = Físico − Comprometido (Equação de Ouro). */
    protected function availableStock(): Attribute
    {
        return Attribute::get(fn (): string => (string) BigDecimal::of($this->physical_stock ?? '0')
            ->minus($this->committed_stock ?? '0')
            ->toScale(4));
    }

    public function isBelowMinimum(): bool
    {
        return $this->min_stock_alert !== null
            && BigDecimal::of($this->available_stock)->isLessThan($this->min_stock_alert);
    }

    // ---- Relacionamentos ----

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    // ---- Scopes ----

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        $like = '%'.addcslashes(trim($term), '%_\\').'%';

        return $query->where(fn (Builder $q) => $q
            ->where('code', 'like', $like)
            ->orWhere('reference', 'like', $like)
            ->orWhere('description', 'like', $like)
            ->orWhere('brand', 'like', $like));
    }

    /** Disponível abaixo do estoque mínimo configurado. */
    public function scopeBelowMinimum(Builder $query): Builder
    {
        return $query->whereNotNull('min_stock_alert')
            ->whereRaw('(physical_stock - committed_stock) < min_stock_alert');
    }

    protected function reference(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => filled($value) ? Str::upper(trim($value), 'UTF-8') : null,
        );
    }

    protected function description(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => filled($value) ? Str::upper(trim($value), 'UTF-8') : $value,
        );
    }

    protected function brand(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => filled($value) ? Str::upper(trim($value), 'UTF-8') : null,
        );
    }
}
