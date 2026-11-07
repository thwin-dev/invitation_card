<?php

namespace App\Modules\Product\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $product = $this->route('product');
        return ['code' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('products', 'code')->ignore($product)], 'name' => ['sometimes', 'required', 'string', 'max:255'], 'category_id' => ['sometimes', 'required', 'integer', Rule::exists('product_categories', 'id')], 'subcategory_id' => ['sometimes', 'required', 'integer', Rule::exists('product_subcategories', 'id')], 'customer_visible' => ['sometimes', 'boolean'], 'photo_path' => ['sometimes', 'nullable', 'string', 'max:255']];
    }
}
