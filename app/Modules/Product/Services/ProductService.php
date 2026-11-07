<?php

namespace App\Modules\Product\Services;

use App\Modules\Product\Models\Product;
use App\Modules\Product\Repositories\ProductRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;

class ProductService
{
    public function __construct(private readonly ProductRepository $products) {}
    public function list(array $filters): LengthAwarePaginator { return $this->products->paginate($filters); }
    public function create(array $data, int $adminId): Product { return Product::query()->create([...Arr::only($data, ['code', 'name', 'category_id', 'subcategory_id', 'customer_visible', 'photo_path']), 'created_by' => $adminId]); }
    public function update(Product $product, array $data): Product { $product->update(Arr::only($data, ['code', 'name', 'category_id', 'subcategory_id', 'customer_visible', 'photo_path'])); return $product->refresh()->load(['category', 'subcategory']); }
}
