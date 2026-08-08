<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable([
    'first_name',
    'last_name',
    'name',
    'email',
    'password',
    'phone',
    'locale',
    'salutation',
    'login_method',
    'profile_image',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
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
     * Display name assembled from the parts. Kept in sync on save so the rest of
     * the app (avatars, greetings, mail) can keep reading a single `name`.
     */
    public function fullName(): string
    {
        $assembled = trim(implode(' ', array_filter([$this->first_name, $this->last_name])));

        return $assembled ?: (string) ($this->name ?: Str::before((string) $this->email, '@'));
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
        return Str::of($this->fullName())
            ->trim()
            ->explode(' ')
            ->take(2)
            ->map(fn (string $part) => Str::upper(Str::substr($part, 0, 1)))
            ->implode('');
    }

    public function isEnabled(): bool
    {
        return (bool) $this->enabled;
    }

    protected static function booted(): void
    {
        // `name` is a projection of first/last — never let the two drift apart.
        static::saving(function (self $user) {
            if ($user->first_name || $user->last_name) {
                $user->name = trim(implode(' ', array_filter([$user->first_name, $user->last_name])));
            }
        });
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
            'enabled' => 'boolean',
            'xp' => 'integer',
        ];
    }
}
