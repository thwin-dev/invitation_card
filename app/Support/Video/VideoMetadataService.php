<?php

namespace App\Support\Video;

use App\Support\Video\Contracts\VideoProviderInterface;
use InvalidArgumentException;

class VideoMetadataService
{
    /**
     * @param  array<int, VideoProviderInterface>  $providers
     */
    public function __construct(private readonly array $providers) {}

    public function extractMeta(string $url): VideoMeta
    {
        foreach ($this->providers as $provider) {
            if ($provider->supports($url)) {
                return $provider->extractMeta($url);
            }
        }

        throw new InvalidArgumentException('Invalid video URL. Please use a YouTube or Vimeo URL.');
    }
}
