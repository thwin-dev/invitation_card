<?php

namespace App\Modules\Order\Models;

use App\Modules\Inventory\Models\ProductUnit;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['order_id', 'product_unit_id', 'quantity', 'estimated_price'])]
class PosOrderItem extends Model
{
    public function order() { return $this->belongsTo(PosOrder::class, 'order_id'); }
    public function unit() { return $this->belongsTo(ProductUnit::class, 'product_unit_id'); }
}
