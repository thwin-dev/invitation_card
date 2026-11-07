<?php

namespace App\Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchUnitPrice extends Model
{
    protected $table = 'branch_unit_prices';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'normal_sale_price' => 'decimal:2',
            'whole_sale_price' => 'decimal:2',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(InventoryBranch::class, 'branch_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(ProductUnit::class, 'product_unit_id');
    }
}
