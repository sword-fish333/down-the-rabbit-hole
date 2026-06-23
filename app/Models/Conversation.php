<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One rabbit hole: a single conversation with a measurable depth. The status is
 * the descent's state machine — see App\Services\Chat\DescentService.
 */
#[Fillable(['user_id', 'subject', 'title', 'current_depth', 'status', 'message_count'])]
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

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function isCheckpointPending(): bool
    {
        return $this->status === self::STATUS_CHECKPOINT_PENDING;
    }

    public function isSurfaced(): bool
    {
        return $this->status === self::STATUS_SURFACED;
    }

    protected function casts(): array
    {
        return [
            'current_depth' => 'integer',
            'message_count' => 'integer',
        ];
    }
}
