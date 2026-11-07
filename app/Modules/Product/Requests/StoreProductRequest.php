<?php

namespace App\Modules\Product\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return ['code' => ['required', 'string', 'max:255', 'unique:products,code'], 'name' => ['required', 'string', 'max:255'], 'category_id' => ['required', 'integer', Rule::exists('product_categories', 'id')], 'subcategory_id' => ['required', 'integer', Rule::exists('product_subcategories', 'id')], 'customer_visible' => ['sometimes', 'boolean'], 'photo_path' => ['sometimes', 'nullable', 'string', 'max:255']];
    }
}
