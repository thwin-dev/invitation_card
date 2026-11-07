<?php

namespace App\Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryLot extends Model
{
    protected $table = 'inventory_lots';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['quantity' => 'integer', 'expires_at' => 'date'];
    }

    public function unit()
    {
        return $this->belongsTo(ProductUnit::class, 'product_unit_id');
    }

    public function branch()
    {
        return $this->belongsTo(InventoryBranch::class);
    }
}
