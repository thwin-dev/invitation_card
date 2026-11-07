<?php

namespace App\Modules\Sale\Models;

use App\Modules\Inventory\Models\InventoryBranch;
use App\Modules\Inventory\Models\ProductUnit;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['branch_id', 'product_unit_id', 'name', 'type', 'value', 'is_active'])]
class DiscountRule extends Model
{
    protected function casts(): array
    {
        return ['value' => 'decimal:2', 'is_active' => 'boolean'];
    }

    public function version(): string
    {
        $branches = $this->branches()->pluck('inventory_branches.id')->sort()->values()->all();
        if ($branches === []) {
            $branches = [(int) $this->branch_id];
        }

        return hash('sha256', json_encode([$this->only(['id', 'branch_id', 'product_unit_id', 'name', 'type', 'value', 'is_active', 'updated_at']), $branches], JSON_THROW_ON_ERROR));
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(InventoryBranch::class);
    }

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(InventoryBranch::class, 'discount_rule_branches', 'discount_rule_id', 'branch_id');
    }

    public function scopeForBranch(Builder $query, int $branchId): Builder
    {
        return $query->where(function (Builder $query) use ($branchId): void {
            $query->where('branch_id', $branchId)->orWhereHas('branches', fn (Builder $branches) => $branches->whereKey($branchId));
        });
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(ProductUnit::class, 'product_unit_id');
    }
}
