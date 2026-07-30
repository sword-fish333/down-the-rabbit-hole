<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * How the guide teaches a layer — Socratic, visual, exam prep, and so on.
 * Content-managed from the admin panel; `prompt_directive` is appended to the
 * per-turn teaching instruction, so the pedagogy is tunable without a deploy.
 */
class LearningMode extends Model
{
    public const string ACCENT_WAVE = 'wave';      // cyan — the default path

    public const string ACCENT_SAND = 'sand';      // tungsten amber — warm/human

    public const string ACCENT_SEA = 'sea';        // absinthe viridian — mastery

    public const string ACCENT_CLAY = 'clay';      // magenta-rose — rare, intense

    public const array ACCENTS = [
        self::ACCENT_WAVE,
        self::ACCENT_SAND,
        self::ACCENT_SEA,
        self::ACCENT_CLAY,
    ];

    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    #[Scope]
    protected function enabled(Builder $query): void
    {
        $query->where('enabled', true);
    }

    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('position')->orderBy('name');
    }

    /**
     * The mode a new hole falls into when the learner doesn't pick one.
     */
    public static function default(): ?self
    {
        return static::query()->enabled()->orderByDesc('is_default')->ordered()->first();
    }

    /**
     * Demote every other default — only one mode may be preselected.
     */
    public function promoteToDefault(): void
    {
        static::query()->whereKeyNot($this->getKey())->update(['is_default' => false]);
        $this->update(['is_default' => true]);
    }

    protected static function booted(): void
    {
        static::saving(function (self $mode) {
            $mode->slug = Str::slug($mode->slug ?: $mode->name);
        });
    }

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'is_default' => 'boolean',
            'position' => 'integer',
        ];
    }
}
