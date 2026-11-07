<?php

namespace App\Support\Video\Concerns;

trait NormalizesVideoText
{
    protected function trimText(string $value, int $limit): string
    {
        return mb_strlen($value) > $limit
            ? mb_substr($value, 0, $limit)
            : $value;
    }

    protected function normalizeHost(string $url): string
    {
        return strtolower((string) parse_url($url, PHP_URL_HOST));
    }
}
