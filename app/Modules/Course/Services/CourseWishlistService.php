<?php

namespace App\Modules\Course\Services;

use App\Models\User;
use App\Modules\Course\Models\CourseWishlist;
use App\Modules\Course\Repositories\CourseWishlistRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CourseWishlistService
{
    public function __construct(
        private readonly CourseWishlistRepository $wishlists,
    ) {}

    public function add(User $student, int $courseId): CourseWishlist
    {
        $course = $this->wishlists->findPublishedCourse($courseId);

        return $this->wishlists->add($student, $course);
    }

    public function remove(User $student, int $courseId): bool
    {
        $course = $this->wishlists->findPublishedCourse($courseId);

        return $this->wishlists->remove($student, $course);
    }

    /** @return LengthAwarePaginator<int, CourseWishlist> */
    public function list(User $student, int $perPage = 12): LengthAwarePaginator
    {
        return $this->wishlists->paginateForStudent($student, $perPage);
    }
}
