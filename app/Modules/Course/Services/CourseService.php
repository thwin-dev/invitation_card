<?php

namespace App\Modules\Course\Services;

use App\Models\User;
use App\Modules\Course\Models\Course;
use App\Modules\Course\Repositories\CourseRepository;
use App\Modules\Course\Support\CourseCatalog;
use App\Modules\Notification\Events\PlatformNotificationRequested;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CourseService
{
    public function __construct(
        private readonly CourseRepository $courses,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Course>
     */
    public function listForManagement(array $filters): LengthAwarePaginator
    {
        return $this->courses->paginateForManagement($filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Course>
     */
    public function listForPortal(array $filters, ?User $student = null): LengthAwarePaginator
    {
        return $this->courses->paginateForPortal($filters, $student);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Course>
     */
    public function listFeaturedForPortal(array $filters, ?User $student = null): LengthAwarePaginator
    {
        return $this->courses->paginateFeaturedForPortal($filters, $student);
    }

    public function findPublishedForPortal(int $id, ?User $student = null): Course
    {
        return $this->courses->findPublishedForPortal($id, $student);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Course
    {
        return DB::transaction(function () use ($data): Course {
            $course = $this->courses->create($this->courseAttributes($data));

            $this->storeThumbnail($course, $data);
            $this->storeBanner($course, $data);
            $this->syncRelationships($course, $data);

            activity()
                ->performedOn($course)
                ->event('course_created')
                ->withProperties(['title' => $course->title])
                ->log('Course created.');

            if ($course->status === CourseCatalog::STATUS_PUBLISHED) {
                PlatformNotificationRequested::dispatch(
                    audience: ['type' => 'all_active_students'],
                    type: 'course.published',
                    title: 'New course available',
                    body: $course->title.' is now available for learning.',
                    data: ['course_id' => $course->id],
                );
            }

            return $this->courses->loadDetails($course);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Course $course, array $data): Course
    {
        return DB::transaction(function () use ($course, $data): Course {
            $wasPublished = $course->status === CourseCatalog::STATUS_PUBLISHED;
            $course = $this->courses->update($course, $this->courseAttributes($data, $course));

            $this->storeThumbnail($course, $data);
            $this->storeBanner($course, $data);
            $this->syncRelationships($course, $data);

            activity()
                ->performedOn($course)
                ->event('course_updated')
                ->withProperties(['title' => $course->title])
                ->log('Course updated.');

            if (! $wasPublished && $course->status === CourseCatalog::STATUS_PUBLISHED) {
                PlatformNotificationRequested::dispatch(
                    audience: ['type' => 'all_active_students'],
                    type: 'course.published',
                    title: 'New course available',
                    body: $course->title.' is now available for learning.',
                    data: ['course_id' => $course->id],
                );
            }

            return $this->courses->loadDetails($course);
        });
    }

    public function archive(Course $course): Course
    {
        return DB::transaction(function () use ($course): Course {
            $course = $this->courses->update($course, [
                'status' => CourseCatalog::STATUS_ARCHIVED,
                'archived_at' => now(),
            ]);

            activity()
                ->performedOn($course)
                ->event('course_archived')
                ->withProperties(['title' => $course->title])
                ->log('Course archived.');

            return $this->courses->loadDetails($course);
        });
    }

    public function toggleFeatured(Course $course): Course
    {
        return DB::transaction(function () use ($course): Course {
            $course = $this->courses->update($course, [
                'is_featured' => ! $course->is_featured,
            ]);

            activity()
                ->performedOn($course)
                ->event($course->is_featured ? 'course_featured' : 'course_unfeatured')
                ->withProperties(['title' => $course->title])
                ->log($course->is_featured ? 'Course marked as featured.' : 'Course removed from featured courses.');

            return $this->courses->loadDetails($course);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function courseAttributes(array $data, ?Course $course = null): array
    {
        $attributes = Arr::only($data, [
            'title',
            'category',
            'level',
            'price_type',
            'price_mmk',
            'short_description',
            'description',
            'duration_minutes',
            'thumbnail_path',
            'banner_path',
            'status',
            'sort_order',
        ]);

        if (array_key_exists('title', $data) || array_key_exists('slug', $data)) {
            $attributes['slug'] = $this->uniqueSlug(
                $data['slug'] ?? Str::slug((string) ($data['title'] ?? $course?->title)),
                $course,
            );
        }

        if (($attributes['price_type'] ?? $course?->price_type) === CourseCatalog::PRICE_FREE) {
            $attributes['price_mmk'] = 0;
        }

        if (($attributes['status'] ?? null) === CourseCatalog::STATUS_PUBLISHED && $course?->published_at === null) {
            $attributes['published_at'] = now();
        }

        if (($attributes['status'] ?? null) === CourseCatalog::STATUS_ARCHIVED) {
            $attributes['archived_at'] = now();
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function storeThumbnail(Course $course, array $data): void
    {
        if (! ($data['thumbnail_file'] ?? null) instanceof UploadedFile) {
            return;
        }

        $path = $data['thumbnail_file']->store("courses/{$course->id}/thumbnail", 'public');

        $this->courses->update($course, [
            'thumbnail_path' => $path,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function storeBanner(Course $course, array $data): void
    {
        if (! ($data['banner_file'] ?? null) instanceof UploadedFile) {
            return;
        }

        $path = $data['banner_file']->store("courses/{$course->id}/banner", 'public');

        $this->courses->update($course, [
            'banner_path' => $path,
        ]);
    }

    private function uniqueSlug(string $slug, ?Course $course = null): string
    {
        $baseSlug = $slug !== '' ? $slug : Str::random(8);
        $candidate = $baseSlug;
        $count = 2;

        while (Course::query()
            ->where('slug', $candidate)
            ->when($course, fn ($query) => $query->whereKeyNot($course->id))
            ->exists()
        ) {
            $candidate = "{$baseSlug}-{$count}";
            $count++;
        }

        return $candidate;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncRelationships(Course $course, array $data): void
    {
        if (array_key_exists('trainer_ids', $data)) {
            $course->trainers()->sync($data['trainer_ids'] ?? []);
        }

        if (array_key_exists('includes', $data)) {
            $course->includes()->delete();

            foreach (array_values($data['includes'] ?? []) as $index => $title) {
                $course->includes()->create([
                    'title' => $title,
                    'sort_order' => $index + 1,
                ]);
            }
        }

        if (array_key_exists('learning_outcomes', $data)) {
            $course->learningOutcomes()->delete();

            foreach (array_values($data['learning_outcomes'] ?? []) as $index => $title) {
                $course->learningOutcomes()->create([
                    'title' => $title,
                    'sort_order' => $index + 1,
                ]);
            }
        }
    }
}
