<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    protected $fillable = ['sale_id', 'floor_id', 'quantity_m2'];

    protected function casts(): array
    {
        return ['quantity_m2' => 'decimal:4'];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }
}
