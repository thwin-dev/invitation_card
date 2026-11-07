<?php

namespace App\Modules\Customer\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

#[Fillable(['name', 'phone', 'address', 'customer_level', 'logo_path', 'qr_code_path', 'created_by'])]
class PosCustomer extends Model
{
    use SoftDeletes;

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected function casts(): array
    {
        return [
            'customer_level' => 'integer',
        ];
    }

    protected function logoUrl(): Attribute
    {
        return Attribute::get(fn (): ?string =>
            $this->logo_path ? Storage::url($this->logo_path) : null
        );
    }

    protected function qrCodeUrl(): Attribute
    {
        return Attribute::get(fn (): ?string =>
            $this->qr_code_path ? Storage::url($this->qr_code_path) : null
        );
    }

    protected function slug(): Attribute
    {
        return Attribute::get(fn (): string =>
            \Illuminate\Support\Str::slug($this->name) ?: 'invitation'
        );
    }

    protected function invitationUrl(): Attribute
    {
        return Attribute::get(fn (): string =>
            route('invitation.card', [
                'customer' => $this->id,
                'slug' => $this->slug,
            ])
        );
    }
}
