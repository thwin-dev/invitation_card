<?php

namespace App\Modules\ProductCategory\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ProductCategory\Models\ProductSubcategory;
use App\Modules\ProductCategory\Requests\StoreProductSubcategoryRequest;
use App\Modules\ProductCategory\Requests\UpdateProductSubcategoryRequest;
use App\Modules\ProductCategory\Resources\ProductSubcategoryResource;
use App\Modules\ProductCategory\Services\ProductSubcategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductSubcategoryController extends Controller
{
    public function __construct(private readonly ProductSubcategoryService $subcategories) {}

    public function index(Request $request): AnonymousResourceCollection { return ProductSubcategoryResource::collection($this->subcategories->list($request->only(['category_id', 'search', 'per_page']))); }

    public function store(StoreProductSubcategoryRequest $request): JsonResponse { return ProductSubcategoryResource::make($this->subcategories->create($request->validated()))->response()->setStatusCode(201); }

    public function show(ProductSubcategory $subcategory): ProductSubcategoryResource { return ProductSubcategoryResource::make($subcategory->load('category')); }

    public function update(UpdateProductSubcategoryRequest $request, ProductSubcategory $subcategory): ProductSubcategoryResource { return ProductSubcategoryResource::make($this->subcategories->update($subcategory, $request->validated())); }

    public function destroy(ProductSubcategory $subcategory): \Illuminate\Http\Response { $subcategory->delete(); return response()->noContent(); }
}
