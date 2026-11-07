<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class EventDetail extends Model
{
    use HasFactory;

    protected $table = 'event_details';

    protected $fillable = [
        'title',
        'subtitle',
        'edition',
        'date_display',
        'start_date',
        'end_date',
        'venue',
        'invitation_text',
        'contact_phone',
        'contact_email',
        'logo_path',
        'banner_path',
        'footer_text',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public static function current(): self
    {
        return static::query()->first() ?? new static([
            'title' => 'Timeless Asia',
            'subtitle' => 'Gems & Jewelry Exhibition',
            'edition' => '4th Edition',
            'date_display' => 'JANUARY 22 - 24 , 2027',
            'venue' => 'Lotte Hotel Yangon Level 2',
            'invitation_text' => 'Cordially Requests The Honor Of Your Presence',
            'contact_phone' => '09450011223',
            'logo_path' => 'images/timeless-asia-logo-white.png',
            'banner_path' => 'images/timeless-asia-banner.jpg',
            'footer_text' => 'Timeless Asia • 4th Edition 2027',
            'is_active' => true,
        ]);
    }

    public function getLogoUrlAttribute(): string
    {
        if ($this->logo_path) {
            if (file_exists(public_path($this->logo_path))) {
                return asset($this->logo_path);
            }
            if (Storage::disk('public')->exists($this->logo_path)) {
                return Storage::disk('public')->url($this->logo_path);
            }
        }

        return asset('images/timeless-asia-logo-white.png');
    }

    public function getBannerUrlAttribute(): string
    {
        if ($this->banner_path) {
            if (file_exists(public_path($this->banner_path))) {
                return asset($this->banner_path);
            }
            if (Storage::disk('public')->exists($this->banner_path)) {
                return Storage::disk('public')->url($this->banner_path);
            }
        }

        return asset('images/timeless-asia-banner.jpg');
    }
}
