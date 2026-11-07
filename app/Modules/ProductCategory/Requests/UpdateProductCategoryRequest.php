<?php

namespace App\Modules\ProductCategory\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductCategoryRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return ['code' => ['sometimes', 'nullable', 'string', 'max:255'], 'name' => ['sometimes', 'required', 'string', 'max:255']];
    }
}
