<?php

namespace App\Modules\Inventory\Models;

use App\Modules\Product\Models\Product;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['product_id', 'from_unit_id', 'to_unit_id', 'quantity'])]
class ProductUnitRelation extends Model
{
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function fromUnit(): BelongsTo { return $this->belongsTo(ProductUnit::class, 'from_unit_id'); }
    public function toUnit(): BelongsTo { return $this->belongsTo(ProductUnit::class, 'to_unit_id'); }
}
