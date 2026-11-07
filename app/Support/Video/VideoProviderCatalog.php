<?php

namespace App\Support\Video;

class VideoProviderCatalog
{
    /**
     * @return array<string, array{slug: string, name: string}>
     */
    public static function keyed(): array
    {
        return [
            'youtube' => [
                'slug' => 'youtube',
                'name' => 'YouTube',
            ],
            'vimeo' => [
                'slug' => 'vimeo',
                'name' => 'Vimeo',
            ],
        ];
    }

    /**
     * @return list<array{slug: string, name: string}>
     */
    public static function all(): array
    {
        return array_values(self::keyed());
    }

    /**
     * @return list<string>
     */
    public static function slugs(): array
    {
        return array_keys(self::keyed());
    }
}
