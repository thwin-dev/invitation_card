<?php

namespace App\Support\Video\Providers;

use App\Support\Video\Concerns\NormalizesVideoText;
use App\Support\Video\Contracts\VideoProviderInterface;
use App\Support\Video\VideoMeta;
use DateInterval;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Throwable;

class YoutubeVideoProvider implements VideoProviderInterface
{
    use NormalizesVideoText;

    public function supports(string $url): bool
    {
        return in_array($this->normalizeHost($url), [
            'youtube.com',
            'www.youtube.com',
            'm.youtube.com',
            'youtu.be',
            'www.youtu.be',
        ], true);
    }

    public function extractMeta(string $url): VideoMeta
    {
        $videoId = $this->extractVideoId($url);

        if (! $videoId) {
            throw new InvalidArgumentException('Invalid YouTube URL.');
        }

        $apiKey = config('services.google.youtube.api_key');

        if (! $apiKey) {
            return $this->fallbackMeta($videoId);
        }

        $response = Http::get('https://www.googleapis.com/youtube/v3/videos', [
            'part' => 'snippet,contentDetails',
            'id' => $videoId,
            'key' => $apiKey,
        ]);

        if (! $response->successful() || empty($response->json('items.0'))) {
            throw new InvalidArgumentException('YouTube video not found.');
        }

        $video = $response->json('items.0');

        return new VideoMeta(
            provider: 'youtube',
            providerVideoId: $videoId,
            title: $video['snippet']['title'] ?? 'YouTube video',
            description: $this->trimText($video['snippet']['description'] ?? '', 500),
            durationSeconds: $this->iso8601ToSeconds($video['contentDetails']['duration'] ?? 'PT0S'),
            thumbnailUrl: $video['snippet']['thumbnails']['high']['url']
                ?? $video['snippet']['thumbnails']['default']['url']
                ?? null,
        );
    }

    private function fallbackMeta(string $videoId): VideoMeta
    {
        return new VideoMeta(
            provider: 'youtube',
            providerVideoId: $videoId,
            title: 'YouTube video',
            description: '',
            durationSeconds: 0,
            thumbnailUrl: "https://img.youtube.com/vi/{$videoId}/hqdefault.jpg",
        );
    }

    private function extractVideoId(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH);
        $host = $this->normalizeHost($url);

        if (in_array($host, ['youtu.be', 'www.youtu.be'], true)) {
            $videoId = trim((string) $path, '/');

            return $this->isValidVideoId($videoId) ? $videoId : null;
        }

        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        if (
            in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com'], true)
            && isset($query['v'])
            && $this->isValidVideoId($query['v'])
        ) {
            return $query['v'];
        }

        if (
            in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com'], true)
            && is_string($path)
            && preg_match('#^/(embed|shorts)/([A-Za-z0-9_-]{11})$#', $path, $matches)
        ) {
            return $matches[2];
        }

        return null;
    }

    private function isValidVideoId(string $videoId): bool
    {
        return preg_match('/^[A-Za-z0-9_-]{11}$/', $videoId) === 1;
    }

    private function iso8601ToSeconds(string $duration): int
    {
        try {
            $interval = new DateInterval($duration);
        } catch (Throwable) {
            return 0;
        }

        return ($interval->d * 86400)
            + ($interval->h * 3600)
            + ($interval->i * 60)
            + $interval->s;
    }
}
