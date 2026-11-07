<?php

namespace App\Modules\Inventory\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['product_unit_id', 'user_id', 'direction', 'quantity', 'quantity_before', 'quantity_after', 'reference_type', 'reference_id', 'note', 'branch_id', 'lot_id', 'operation_key'])]
class StockMovement extends Model
{
    public function branch(): BelongsTo
    {
        return $this->belongsTo(InventoryBranch::class);
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(InventoryLot::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(ProductUnit::class, 'product_unit_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
