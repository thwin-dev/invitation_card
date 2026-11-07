<?php

namespace App\Modules\ProductCategory\Services;

use App\Modules\ProductCategory\Models\ProductCategory;
use App\Modules\ProductCategory\Repositories\ProductCategoryRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;

class ProductCategoryService
{
    public function __construct(private readonly ProductCategoryRepository $categories) {}

    public function list(array $filters): LengthAwarePaginator { return $this->categories->paginate($filters); }

    public function create(array $data, int $adminId): ProductCategory
    {
        return ProductCategory::query()->create([...Arr::only($data, ['code', 'name']), 'created_by' => $adminId]);
    }

    public function update(ProductCategory $category, array $data): ProductCategory
    {
        $category->update(Arr::only($data, ['code', 'name']));
        return $category->refresh();
    }
}
