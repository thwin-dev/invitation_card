<?php

namespace App\Modules\Inventory\Models;

use App\Modules\Product\Models\Product;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['product_id', 'from_unit_id', 'from_unit_quantity', 'to_unit_id', 'to_unit_quantity'])]
class ProductUnitConversion extends Model
{
    public function product() { return $this->belongsTo(Product::class); }
    public function fromUnit() { return $this->belongsTo(ProductUnit::class, 'from_unit_id'); }
    public function toUnit() { return $this->belongsTo(ProductUnit::class, 'to_unit_id'); }
}
