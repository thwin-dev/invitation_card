<?php

namespace App\Modules\ProductCategory\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'name', 'category_id', 'is_active'])]
class ProductSubcategory extends Model
{
    protected function casts(): array { return ['is_active' => 'boolean']; }
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(\App\Modules\Product\Models\Product::class, 'subcategory_id');
    }
}
