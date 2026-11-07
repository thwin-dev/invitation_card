<?php

namespace App\Modules\Course\Resources;

use App\Support\Media\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrainerSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'title' => $this->title,
            'specialization' => $this->specialization,
            'portrait_path' => MediaUrl::for($this->portrait_path),
            'status' => $this->status,
        ];
    }
}
