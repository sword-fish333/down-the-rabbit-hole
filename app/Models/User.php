<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable(['name', 'email', 'password', 'login_method', 'profile_image'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    const array LOGIN_METHODS = ['auth', 'sanctum', 'google', 'facebook'];

    public const string AUTH_LOGIN_METHOD = 'auth';

    public const string GOOGLE_LOGIN_METHOD = 'google';

    public const string IMAGES_DIRECTORY = 'users';

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function xpEvents(): HasMany
    {
        return $this->hasMany(XpEvent::class);
    }

    public function streak(): HasOne
    {
        return $this->hasOne(Streak::class);
    }

    /**
     * Resolved avatar URL, or null when the learner has no picture (caller renders initials).
     * External (http) avatars — e.g. from Google — are passed through untouched.
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

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'xp' => 'integer',
        ];
    }
}
