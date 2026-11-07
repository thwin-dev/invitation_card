<?php

namespace App\Modules\Product\Repositories;

use App\Modules\Product\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProductRepository
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        return Product::query()->with(['category', 'subcategory'])
            ->when($filters['category_id'] ?? null, fn ($query, mixed $id) => $query->where('category_id', $id))
            ->when($filters['subcategory_id'] ?? null, fn ($query, mixed $id) => $query->where('subcategory_id', $id))
            ->when($filters['search'] ?? null, fn ($query, string $search) => $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%");
            }))
            ->orderBy('name')->paginate((int) ($filters['per_page'] ?? 15));
    }
}
