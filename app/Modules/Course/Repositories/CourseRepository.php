<?php

namespace App\Modules\Course\Repositories;

use App\Models\User;
use App\Modules\Course\Models\Course;
use App\Modules\Course\Support\CourseCatalog;
use App\Modules\Curriculum\Support\CurriculumCatalog;
use App\Modules\Student\Models\StudentCourseAccess;
use App\Modules\Student\Support\StudentCatalog;
use App\Modules\Trainer\Support\TrainerCatalog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CourseRepository
{
    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Course>
     */
    public function paginateForManagement(array $filters): LengthAwarePaginator
    {
        return Course::query()
            ->with(['trainers', 'includes', 'learningOutcomes'])
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('title', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('short_description', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($filters['category'] ?? null, fn ($query, string $category) => $query->where('category', $category))
            ->when($filters['level'] ?? null, fn ($query, string $level) => $query->where('level', $level))
            ->when($filters['price_type'] ?? null, fn ($query, string $priceType) => $query->where('price_type', $priceType))
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->paginate((int) ($filters['per_page'] ?? 15));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Course>
     */
    public function paginateForPortal(array $filters, ?User $student = null): LengthAwarePaginator
    {
        return Course::query()
            ->with(['trainers' => fn ($query) => $query->where('trainers.status', TrainerCatalog::STATUS_PUBLISHED)])
            ->when($student, fn ($query) => $query->withExists([
                'wishlists as is_wishlisted' => fn ($query) => $query->where('user_id', $student->id),
                'courseAccesses as is_enrolled' => fn ($query) => $query
                    ->where('user_id', $student->id)
                    ->where('status', StudentCatalog::ACCESS_STATUS_ACTIVE),
            ])->addSelect([
                'enrollment_status' => StudentCourseAccess::query()
                    ->select('status')
                    ->whereColumn('course_id', 'courses.id')
                    ->where('user_id', $student->id)
                    ->latest()
                    ->limit(1),
            ]))
            ->where('status', CourseCatalog::STATUS_PUBLISHED)
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('title', 'like', "%{$search}%")
                        ->orWhere('short_description', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($filters['category'] ?? null, fn ($query, string $category) => $query->where('category', $category))
            ->when($filters['level'] ?? null, fn ($query, string $level) => $query->where('level', $level))
            ->when($filters['price_type'] ?? null, fn ($query, string $priceType) => $query->where('price_type', $priceType))
            ->orderBy('sort_order')
            ->orderByDesc('published_at')
            ->paginate((int) ($filters['per_page'] ?? 12));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Course>
     */
    public function paginateFeaturedForPortal(array $filters, ?User $student = null): LengthAwarePaginator
    {
        return Course::query()
            ->with(['trainers' => fn ($query) => $query->where('trainers.status', TrainerCatalog::STATUS_PUBLISHED)])
            ->when($student, fn ($query) => $query->withExists([
                'wishlists as is_wishlisted' => fn ($query) => $query->where('user_id', $student->id),
                'courseAccesses as is_enrolled' => fn ($query) => $query
                    ->where('user_id', $student->id)
                    ->where('status', StudentCatalog::ACCESS_STATUS_ACTIVE),
            ])->addSelect([
                'enrollment_status' => StudentCourseAccess::query()
                    ->select('status')
                    ->whereColumn('course_id', 'courses.id')
                    ->where('user_id', $student->id)
                    ->latest()
                    ->limit(1),
            ]))
            ->where('status', CourseCatalog::STATUS_PUBLISHED)
            ->where('is_featured', true)
            ->orderBy('sort_order')
            ->orderByDesc('published_at')
            ->paginate((int) ($filters['per_page'] ?? 12));
    }

    public function findPublishedForPortal(int $id, ?User $student = null): Course
    {
        return Course::query()
            ->whereKey($id)
            ->where('status', CourseCatalog::STATUS_PUBLISHED)
            ->when($student, fn ($query) => $query->withExists([
                'wishlists as is_wishlisted' => fn ($query) => $query->where('user_id', $student->id),
                'courseAccesses as is_enrolled' => fn ($query) => $query
                    ->where('user_id', $student->id)
                    ->where('status', StudentCatalog::ACCESS_STATUS_ACTIVE),
            ])->addSelect([
                'enrollment_status' => StudentCourseAccess::query()
                    ->select('status')
                    ->whereColumn('course_id', 'courses.id')
                    ->where('user_id', $student->id)
                    ->latest()
                    ->limit(1),
            ]))
            ->with([
                'trainers' => fn ($query) => $query->where('trainers.status', TrainerCatalog::STATUS_PUBLISHED),
                'includes',
                'learningOutcomes',
                'modules' => fn ($query) => $query->where('status', CurriculumCatalog::STATUS_PUBLISHED),
                'modules.lessons' => fn ($query) => $query->where('status', CurriculumCatalog::STATUS_PUBLISHED),
                'modules.lessons.resources',
            ])
            ->firstOrFail();
    }

    public function create(array $attributes): Course
    {
        return Course::query()->create($attributes);
    }

    public function update(Course $course, array $attributes): Course
    {
        $course->update($attributes);

        return $course;
    }

    public function loadDetails(Course $course): Course
    {
        return $course->load(['trainers', 'includes', 'learningOutcomes']);
    }
}
