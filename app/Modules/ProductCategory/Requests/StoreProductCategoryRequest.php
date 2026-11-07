<?php

namespace App\Modules\ProductCategory\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductCategoryRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return ['code' => ['nullable', 'string', 'max:255'], 'name' => ['required', 'string', 'max:255']];
    }
}
