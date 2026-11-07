<?php

namespace App\Modules\Product\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Requests\StoreProductRequest;
use App\Modules\Product\Requests\UpdateProductRequest;
use App\Modules\Product\Resources\ProductResource;
use App\Modules\Product\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function __construct(private readonly ProductService $products) {}
    public function index(Request $request): AnonymousResourceCollection { return ProductResource::collection($this->products->list($request->only(['category_id', 'subcategory_id', 'search', 'per_page']))); }
    public function store(StoreProductRequest $request): JsonResponse { return ProductResource::make($this->products->create($request->validated(), $request->user()->id))->response()->setStatusCode(201); }
    public function show(Product $product): ProductResource { return ProductResource::make($product->load(['category', 'subcategory'])); }
    public function update(UpdateProductRequest $request, Product $product): ProductResource { return ProductResource::make($this->products->update($product, $request->validated())); }
    public function destroy(Product $product): \Illuminate\Http\Response { $product->delete(); return response()->noContent(); }
}
