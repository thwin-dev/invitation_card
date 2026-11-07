<?php

namespace App\Modules\ProductCategory\Repositories;

use App\Modules\ProductCategory\Models\ProductSubcategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProductSubcategoryRepository
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        return ProductSubcategory::query()
            ->with('category')
            ->when($filters['category_id'] ?? null, fn ($query, mixed $id) => $query->where('category_id', $id))
            ->when($filters['search'] ?? null, fn ($query, string $search) => $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%");
            }))
            ->orderBy('name')
            ->paginate((int) ($filters['per_page'] ?? 15));
    }
}
