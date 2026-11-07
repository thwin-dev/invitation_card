<?php

namespace App\Modules\Course\Repositories;

use App\Models\User;
use App\Modules\Course\Models\Course;
use App\Modules\Course\Models\CourseWishlist;
use App\Modules\Course\Support\CourseCatalog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CourseWishlistRepository
{
    public function findPublishedCourse(int $courseId): Course
    {
        return Course::query()
            ->whereKey($courseId)
            ->where('status', CourseCatalog::STATUS_PUBLISHED)
            ->firstOrFail();
    }

    public function add(User $student, Course $course): CourseWishlist
    {
        return CourseWishlist::query()->firstOrCreate([
            'user_id' => $student->id,
            'course_id' => $course->id,
        ])->load('course');
    }

    public function remove(User $student, Course $course): bool
    {
        return CourseWishlist::query()
            ->where('user_id', $student->id)
            ->where('course_id', $course->id)
            ->delete() > 0;
    }

    /** @return LengthAwarePaginator<int, CourseWishlist> */
    public function paginateForStudent(User $student, int $perPage = 12): LengthAwarePaginator
    {
        return CourseWishlist::query()
            ->with(['course.trainers'])
            ->where('user_id', $student->id)
            ->whereHas('course', fn ($query) => $query->where('status', CourseCatalog::STATUS_PUBLISHED))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }
}
