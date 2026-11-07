<?php

namespace App\Modules\ProductCategory\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ProductCategory\Models\ProductCategory;
use App\Modules\ProductCategory\Requests\StoreProductCategoryRequest;
use App\Modules\ProductCategory\Requests\UpdateProductCategoryRequest;
use App\Modules\ProductCategory\Resources\ProductCategoryResource;
use App\Modules\ProductCategory\Services\ProductCategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductCategoryController extends Controller
{
    public function __construct(private readonly ProductCategoryService $categories) {}

    public function index(Request $request): AnonymousResourceCollection { return ProductCategoryResource::collection($this->categories->list($request->only(['search', 'per_page']))); }

    public function store(StoreProductCategoryRequest $request): JsonResponse
    {
        return ProductCategoryResource::make($this->categories->create($request->validated(), $request->user()->id))->response()->setStatusCode(201);
    }

    public function show(ProductCategory $category): ProductCategoryResource { return ProductCategoryResource::make($category); }

    public function update(UpdateProductCategoryRequest $request, ProductCategory $category): ProductCategoryResource { return ProductCategoryResource::make($this->categories->update($category, $request->validated())); }

    public function destroy(ProductCategory $category): \Illuminate\Http\Response { $category->delete(); return response()->noContent(); }
}
