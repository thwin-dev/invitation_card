<?php

namespace App\Support\Video;

class VideoMeta
{
    public function __construct(
        public readonly string $provider,
        public readonly string $providerVideoId,
        public readonly string $title,
        public readonly string $description,
        public readonly int $durationSeconds,
        public readonly ?string $thumbnailUrl,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'provider' => $this->provider,
            'provider_video_id' => $this->providerVideoId,
            'title' => $this->title,
            'description' => $this->description,
            'duration_seconds' => $this->durationSeconds,
            'thumbnail_url' => $this->thumbnailUrl,
        ];
    }
}
