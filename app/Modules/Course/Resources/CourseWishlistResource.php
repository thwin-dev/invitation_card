<?php

namespace App\Modules\Course\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseWishlistResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->user_id,
            'course_id' => $this->course_id,
            'course' => PortalCourseResource::make($this->whenLoaded('course')),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
