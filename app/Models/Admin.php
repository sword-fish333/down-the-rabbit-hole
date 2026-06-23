<?php

namespace App\Models;

use Database\Factories\AdminFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class Admin extends Authenticatable
{
    /** @use HasFactory<AdminFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public const array ROLES = ['main-admin', 'manager', 'developer'];

    public const array LOGIN_METHODS = ['credentials', 'google', 'github', 'facebook'];

    public const string CREDENTIALS_LOGIN_METHOD = 'credentials';

    public const string GOOGLE_LOGIN_METHOD = 'google';

    public const string IMAGES_DIRECTORY = 'admins';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'enabled' => 'boolean',
        ];
    }

    public function isEnabled(): bool
    {
        return (bool) $this->enabled;
    }

    /**
     * Resolved avatar URL, or null when the admin has no picture (caller renders initials).
     */
    public function profileImageUrl(): ?string
    {
        if (! $this->profile_image) {
            return null;
        }

        return str_starts_with($this->profile_image, 'http')
            ? $this->profile_image
            : Storage::disk('public')->url(self::IMAGES_DIRECTORY.'/'.$this->profile_image);
    }

    /**
     * Up to two uppercase initials for the avatar fallback.
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->trim()
            ->explode(' ')
            ->take(2)
            ->map(fn (string $part) => Str::upper(Str::substr($part, 0, 1)))
            ->implode('');
    }
}
