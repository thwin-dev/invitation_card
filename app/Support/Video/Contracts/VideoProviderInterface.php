<?php

namespace App\Support\Video\Contracts;

use App\Support\Video\VideoMeta;

interface VideoProviderInterface
{
    public function supports(string $url): bool;

    public function extractMeta(string $url): VideoMeta;
}
