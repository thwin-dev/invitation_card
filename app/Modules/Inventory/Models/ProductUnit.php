<?php

namespace App\Modules\Inventory\Models;

use App\Modules\Product\Models\Product;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['product_id', 'code', 'original_code', 'name', 'current_quantity', 'reorder_quantity', 'normal_sale_price', 'whole_sale_price', 'order_price', 'purchase_price', 'size', 'color', 'tracking'])]
class ProductUnit extends Model
{
    use SoftDeletes;

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function components()
    {
        return $this->belongsToMany(self::class, 'bundle_components', 'bundle_unit_id', 'component_unit_id')->withPivot('quantity');
    }

    protected function casts(): array
    {
        return ['current_quantity' => 'integer', 'reorder_quantity' => 'integer', 'normal_sale_price' => 'decimal:2', 'whole_sale_price' => 'decimal:2', 'order_price' => 'decimal:2', 'purchase_price' => 'decimal:2'];
    }
}
