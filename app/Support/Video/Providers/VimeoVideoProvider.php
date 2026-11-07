<?php

namespace App\Support\Video\Providers;

use App\Support\Video\Concerns\NormalizesVideoText;
use App\Support\Video\Contracts\VideoProviderInterface;
use App\Support\Video\VideoMeta;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class VimeoVideoProvider implements VideoProviderInterface
{
    use NormalizesVideoText;

    public function supports(string $url): bool
    {
        $host = $this->normalizeHost($url);

        return $host === 'vimeo.com'
            || str_ends_with($host, '.vimeo.com');
    }

    public function extractMeta(string $url): VideoMeta
    {
        $response = Http::get('https://vimeo.com/api/oembed.json', [
            'url' => $url,
        ]);

        if (! $response->successful()) {
            throw new InvalidArgumentException('Vimeo video not found.');
        }

        $video = $response->json();

        return new VideoMeta(
            provider: 'vimeo',
            providerVideoId: (string) ($video['video_id'] ?? ''),
            title: $video['title'] ?? 'Vimeo video',
            description: $this->trimText(strip_tags($video['description'] ?? ''), 500),
            durationSeconds: (int) ($video['duration'] ?? 0),
            thumbnailUrl: $video['thumbnail_url'] ?? null,
        );
    }
}
