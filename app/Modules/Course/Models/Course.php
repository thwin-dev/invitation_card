<?php

namespace App\Modules\Course\Models;

use App\Modules\Curriculum\Models\CourseModule;
use App\Modules\Certificate\Models\CertificateTemplate;
use App\Modules\Student\Models\StudentCourseAccess;
use App\Modules\Learning\Models\LessonProgress;
use App\Modules\Trainer\Models\Trainer;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'title',
    'slug',
    'category',
    'level',
    'price_type',
    'price_mmk',
    'short_description',
    'description',
    'duration_minutes',
    'thumbnail_path',
    'banner_path',
    'is_featured',
    'status',
    'sort_order',
    'published_at',
    'archived_at',
])]
class Course extends Model
{
    /**
     * @return BelongsToMany<Trainer, $this>
     */
    public function trainers(): BelongsToMany
    {
        return $this->belongsToMany(Trainer::class)->withTimestamps();
    }

    /**
     * @return HasMany<CourseInclude, $this>
     */
    public function includes(): HasMany
    {
        return $this->hasMany(CourseInclude::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<CourseLearningOutcome, $this>
     */
    public function learningOutcomes(): HasMany
    {
        return $this->hasMany(CourseLearningOutcome::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<CourseModule, $this>
     */
    public function modules(): HasMany
    {
        return $this->hasMany(CourseModule::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<CourseWishlist, $this>
     */
    public function wishlists(): HasMany
    {
        return $this->hasMany(CourseWishlist::class);
    }

    /**
     * @return HasMany<StudentCourseAccess, $this>
     */
    public function courseAccesses(): HasMany
    {
        return $this->hasMany(StudentCourseAccess::class);
    }

    public function certificateTemplate(): HasOne
    {
        return $this->hasOne(CertificateTemplate::class);
    }

    public function lessonProgresses(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }

    public function lessons(): HasManyThrough
    {
        return $this->hasManyThrough(
            \App\Modules\Curriculum\Models\Lesson::class,
            CourseModule::class,
            'course_id',
            'course_module_id',
        );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price_mmk' => 'integer',
            'duration_minutes' => 'integer',
            'is_featured' => 'boolean',
            'sort_order' => 'integer',
            'published_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }
}
