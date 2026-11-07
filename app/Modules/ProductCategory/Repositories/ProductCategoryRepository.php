<?php

namespace App\Modules\ProductCategory\Repositories;

use App\Modules\ProductCategory\Models\ProductCategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProductCategoryRepository
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        return ProductCategory::query()
            ->when($filters['search'] ?? null, fn ($query, string $search) => $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%");
            }))
            ->orderBy('name')
            ->paginate((int) ($filters['per_page'] ?? 15));
    }
}
