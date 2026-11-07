<?php

namespace App\Modules\Course\Requests;

use App\Modules\Course\Support\CourseCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash:ascii', Rule::unique('courses', 'slug')],
            'category' => ['required', 'string', Rule::in(CourseCatalog::categories())],
            'level' => ['required', 'string', Rule::in(CourseCatalog::levels())],
            'price_type' => ['required', 'string', Rule::in(CourseCatalog::priceTypes())],
            'price_mmk' => ['nullable', 'integer', 'min:0'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'description' => ['required', 'string'],
            'duration_minutes' => ['nullable', 'integer', 'min:1'],
            'thumbnail_path' => ['nullable', 'string', 'max:2048'],
            'thumbnail_file' => ['nullable', 'image', 'max:5120'],
            'banner_path' => ['nullable', 'string', 'max:2048'],
            'banner_file' => ['nullable', 'image', 'max:10240'],
            'status' => ['required', 'string', Rule::in(CourseCatalog::statuses())],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'trainer_ids' => ['nullable', 'array'],
            'trainer_ids.*' => ['integer', Rule::exists('trainers', 'id')],
            'includes' => ['nullable', 'array'],
            'includes.*' => ['required', 'string', 'max:255'],
            'learning_outcomes' => ['nullable', 'array'],
            'learning_outcomes.*' => ['required', 'string', 'max:255'],
        ];
    }
}
