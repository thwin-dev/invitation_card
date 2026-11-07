<?php

namespace App\Modules\Sale\Models;

use App\Modules\Inventory\Models\ProductUnit;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['sale_id', 'product_unit_id', 'quantity', 'price', 'discount_rule_id', 'discount_name', 'discount_type', 'discount_value', 'discount_amount'])]
class SaleItem extends Model
{
    protected function casts(): array
    {
        return ['price' => 'decimal:2', 'discount_value' => 'decimal:2', 'discount_amount' => 'decimal:2'];
    }

    public function getNetTotalAttribute(): float
    {
        return round($this->quantity * (float) $this->price - (float) $this->discount_amount, 2);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(ProductUnit::class, 'product_unit_id');
    }
}
