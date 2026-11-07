<?php

namespace App\Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class InventoryBranch extends Model
{
    protected $table = 'inventory_branches';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_main' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForSales(Builder $query): Builder
    {
        return $query->active()->where('type', 'shop');
    }
}
