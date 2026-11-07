<?php

namespace App\Modules\Course\Support;

class CourseCatalog
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_HIDDEN = 'hidden';

    public const STATUS_ARCHIVED = 'archived';

    public const PRICE_FREE = 'free';

    public const PRICE_PREMIUM = 'premium';

    /**
     * @return list<string>
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_DRAFT,
            self::STATUS_PUBLISHED,
            self::STATUS_HIDDEN,
            self::STATUS_ARCHIVED,
        ];
    }

    /**
     * @return list<string>
     */
    public static function categories(): array
    {
        return [
            'leadership',
            'nlp-excellence',
            'business-strategy',
            'coaching',
        ];
    }

    /**
     * @return list<string>
     */
    public static function levels(): array
    {
        return [
            'beginner',
            'intermediate',
            'executive',
            'senior-manager',
        ];
    }

    /**
     * @return list<string>
     */
    public static function priceTypes(): array
    {
        return [
            self::PRICE_FREE,
            self::PRICE_PREMIUM,
        ];
    }
}
