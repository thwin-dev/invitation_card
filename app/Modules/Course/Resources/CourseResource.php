<?php

namespace App\Modules\Course\Resources;

use App\Support\Media\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
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
            'status' => $this->status,
            'sort_order' => $this->sort_order,
            'published_at' => $this->published_at?->toISOString(),
            'archived_at' => $this->archived_at?->toISOString(),
            'trainers' => TrainerSummaryResource::collection($this->whenLoaded('trainers')),
            'includes' => $this->whenLoaded('includes', fn () => $this->includes->pluck('title')->values()),
            'learning_outcomes' => $this->whenLoaded('learningOutcomes', fn () => $this->learningOutcomes->pluck('title')->values()),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
