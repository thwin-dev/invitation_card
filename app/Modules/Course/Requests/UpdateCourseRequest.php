<?php

namespace App\Modules\Course\Requests;

use App\Modules\Course\Models\Course;
use App\Modules\Course\Support\CourseCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCourseRequest extends FormRequest
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
        $course = $this->route('course');
        $courseId = $course instanceof Course ? $course->id : null;

        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:255', 'alpha_dash:ascii', Rule::unique('courses', 'slug')->ignore($courseId)],
            'category' => ['sometimes', 'required', 'string', Rule::in(CourseCatalog::categories())],
            'level' => ['sometimes', 'required', 'string', Rule::in(CourseCatalog::levels())],
            'price_type' => ['sometimes', 'required', 'string', Rule::in(CourseCatalog::priceTypes())],
            'price_mmk' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'short_description' => ['sometimes', 'nullable', 'string', 'max:500'],
            'description' => ['sometimes', 'required', 'string'],
            'duration_minutes' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'thumbnail_path' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'thumbnail_file' => ['sometimes', 'nullable', 'image', 'max:5120'],
            'banner_path' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'banner_file' => ['sometimes', 'nullable', 'image', 'max:10240'],
            'status' => ['sometimes', 'required', 'string', Rule::in(CourseCatalog::statuses())],
            'sort_order' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'trainer_ids' => ['sometimes', 'array'],
            'trainer_ids.*' => ['integer', Rule::exists('trainers', 'id')],
            'includes' => ['sometimes', 'array'],
            'includes.*' => ['required', 'string', 'max:255'],
            'learning_outcomes' => ['sometimes', 'array'],
            'learning_outcomes.*' => ['required', 'string', 'max:255'],
        ];
    }
}
