<?php

namespace App\Modules\ProductCategory\Services;

use App\Modules\ProductCategory\Models\ProductSubcategory;
use App\Modules\ProductCategory\Repositories\ProductSubcategoryRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;

class ProductSubcategoryService
{
    public function __construct(private readonly ProductSubcategoryRepository $subcategories) {}

    public function list(array $filters): LengthAwarePaginator { return $this->subcategories->paginate($filters); }

    public function create(array $data): ProductSubcategory { return ProductSubcategory::query()->create(Arr::only($data, ['code', 'name', 'category_id'])); }

    public function update(ProductSubcategory $subcategory, array $data): ProductSubcategory
    {
        $subcategory->update(Arr::only($data, ['code', 'name', 'category_id']));
        return $subcategory->refresh()->load('category');
    }
}
