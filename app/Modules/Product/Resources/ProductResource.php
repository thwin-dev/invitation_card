<?php

namespace App\Modules\Product\Resources;

use App\Modules\ProductCategory\Resources\ProductCategoryResource;
use App\Modules\ProductCategory\Resources\ProductSubcategoryResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'code' => $this->code, 'name' => $this->name, 'category_id' => $this->category_id, 'subcategory_id' => $this->subcategory_id, 'customer_visible' => $this->customer_visible, 'photo_path' => $this->photo_path, 'category' => ProductCategoryResource::make($this->whenLoaded('category')), 'subcategory' => ProductSubcategoryResource::make($this->whenLoaded('subcategory')), 'created_at' => $this->created_at?->toISOString(), 'updated_at' => $this->updated_at?->toISOString()];
    }
}
