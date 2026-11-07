<?php

namespace App\Modules\Course\Resources;

use App\Support\Media\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PortalCourseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isEnrolled = (bool) ($this->is_enrolled ?? false);

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'category' => $this->category,
            'level' => $this->level,
            'price_type' => $this->price_type,
            'price_mmk' => $this->price_mmk,
            'short_description' => $this->short_description,
            'description' => $this->description,
            'duration_minutes' => $this->duration_minutes,
            'thumbnail_path' => MediaUrl::for($this->thumbnail_path),
            'banner_path' => MediaUrl::for($this->banner_path),
            'is_featured' => $this->is_featured,
            'is_wishlisted' => $this->when(
                $request->user() !== null,
                fn () => (bool) ($this->is_wishlisted ?? false),
            ),
            'is_enrolled' => $this->when(
                $request->user() !== null,
                fn () => (bool) ($this->is_enrolled ?? false),
            ),
            'enrollment_status' => $this->when(
                $request->user() !== null,
                fn () => $this->enrollment_status,
            ),
            'trainers' => PortalCourseTrainerResource::collection($this->whenLoaded('trainers')),
            'includes' => $this->whenLoaded('includes', fn () => $this->includes->pluck('title')->values()),
            'learning_outcomes' => $this->whenLoaded('learningOutcomes', fn () => $this->learningOutcomes->pluck('title')->values()),
            'modules' => $this->whenLoaded('modules', fn () => $this->modules->map(fn ($module) => [
                'id' => $module->id,
                'title' => $module->title,
                'description' => $module->description,
                'sort_order' => $module->sort_order,
                'lessons' => $module->lessons->map(function ($lesson) use ($isEnrolled): array {
                    $isLocked = ! $isEnrolled && ! $lesson->is_preview;

                    $lessonData = [
                        'id' => $lesson->id,
                        'title' => $lesson->title,
                        'description' => $lesson->description,
                        'video_provider' => $lesson->video_provider,
                        'video_duration_seconds' => $lesson->video_duration_seconds,
                        'video_duration' => gmdate('H:i:s', (int) $lesson->video_duration_seconds),
                        'is_preview' => $lesson->is_preview,
                        'is_locked' => $isLocked,
                        'sort_order' => $lesson->sort_order,
                    ];

                    if (! $isLocked) {
                        $lessonData['video_url'] = $lesson->video_url;
                        $lessonData['resources'] = $lesson->resources->map(fn ($resource) => [
                            'id' => $resource->id,
                            'lesson_id' => $resource->lesson_id,
                            'title' => $resource->title,
                            'type' => $resource->type,
                            'url' => $resource->url,
                            'file_path' => $resource->file_path,
                            'sort_order' => $resource->sort_order,
                        ])->values();
                    }

                    return $lessonData;
                })->values(),
            ])->values()),
        ];
    }
}
