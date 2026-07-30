<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One rabbit hole: a single conversation with a measurable depth. The status is
 * the descent's state machine — see App\Services\Chat\DescentService.
 */
#[Fillable([
    'user_id',
    'learning_mode_id',
    'subject',
    'title',
    'current_depth',
    'status',
    'summary',
    'message_count',
    'surfaced_at',
])]
class Conversation extends Model
{
    public const string STATUS_EXPLORING = 'exploring';

    public const string STATUS_CHECKPOINT_PENDING = 'checkpoint_pending';

    public const string STATUS_SURFACED = 'surfaced';

    public const array STATUSES = [
        self::STATUS_EXPLORING,
        self::STATUS_CHECKPOINT_PENDING,
        self::STATUS_SURFACED,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function learningMode(): BelongsTo
    {
        return $this->belongsTo(LearningMode::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function concepts(): HasMany
    {
        return $this->hasMany(Concept::class);
    }

    public function checkpointAttempts(): HasMany
    {
        return $this->hasMany(CheckpointAttempt::class);
    }

    #[Scope]
    protected function ownedBy(Builder $query, ?int $userId): void
    {
        $query->where('user_id', $userId);
    }

    public function isCheckpointPending(): bool
    {
        return $this->status === self::STATUS_CHECKPOINT_PENDING;
    }

    public function isSurfaced(): bool
    {
        return $this->status === self::STATUS_SURFACED;
    }

    /** Depth as a 0..1 fraction — drives the atmosphere and the depth rail. */
    public function progress(): float
    {
        $max = (int) config('platform.chat.max_depth');

        return $max > 0 ? min(1, $this->current_depth / $max) : 0.0;
    }

    /** A short human label for lists, falling back to the raw subject. */
    public function displayTitle(): string
    {
        return $this->title ?: $this->subject;
    }

    protected function casts(): array
    {
        return [
            'current_depth' => 'integer',
            'message_count' => 'integer',
            'surfaced_at' => 'datetime',
        ];
    }
}
