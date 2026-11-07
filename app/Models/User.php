<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Modules\Course\Models\CourseWishlist;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['user_code', 'name', 'email', 'phone', 'password', 'role', 'role_id', 'prohibition_flag', 'photo_path', 'status', 'last_login_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'prohibition_flag' => 'boolean',
        ];
    }

    /**
     * @return HasMany<CourseWishlist, $this>
     */
    public function courseWishlists(): HasMany
    {
        return $this->hasMany(CourseWishlist::class);
    }

    public function roleRecord(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function hasPermissionTo(string $module, string $action): bool
    {
        if (! \App\Support\PermissionCatalog::contains($module, $action)) {
            return false;
        }

        if ($this->role_id === null) {
            // Keep legacy Owner accounts usable until the permission seeder assigns their role_id.
            return strtolower(trim((string) $this->role)) === 'owner';
        }

        $this->loadMissing('roleRecord.permissions');

        return $this->roleRecord?->permissions->contains(fn (Permission $permission): bool =>
            $permission->module === $module && $permission->action === $action
        ) ?? false;
    }

    public static function hasOtherActiveAdmin(int $exceptUserId): bool
    {
        return static::query()
            ->where('role_id', Role::query()->where('slug', 'admin')->value('id'))
            ->where('id', '!=', $exceptUserId)
            ->where('prohibition_flag', false)
            ->exists();
    }

}
