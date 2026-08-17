<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One node on a hole's mastery map. State is only ever moved by graded evidence
 * (see App\Services\Chat\MasteryService) — never by the learner asserting it.
 */
#[Fillable([
    'conversation_id',
    'name',
    'slug',
    'state',
    'first_seen_depth',
    'last_seen_depth',
    'note',
    'reviewed_at',
    'mastered_at',
])]
class Concept extends Model
{
    public const string STATE_UNEXPLORED = 'unexplored';

    public const string STATE_DEVELOPING = 'developing';

    public const string STATE_MISUNDERSTOOD = 'misunderstood';

    public const string STATE_MASTERED = 'mastered';

    public const array STATES = [
        self::STATE_UNEXPLORED,
        self::STATE_DEVELOPING,
        self::STATE_MISUNDERSTOOD,
        self::STATE_MASTERED,
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    #[Scope]
    protected function needingReview(Builder $query): void
    {
        $query->where('state', self::STATE_MISUNDERSTOOD);
    }

    public function isMastered(): bool
    {
        return $this->state === self::STATE_MASTERED;
    }

    /**
     * Has this ever been proven mastered? Distinct from `isMastered()`, which is
     * where it stands *now* — a concept fumbled later moves back to
     * misunderstood, and the proof it once earned is not undone by that.
     */
    public function wasEverMastered(): bool
    {
        return $this->mastered_at !== null;
    }

    public function isMisunderstood(): bool
    {
        return $this->state === self::STATE_MISUNDERSTOOD;
    }

    /**
     * True once this concept was first met on an earlier layer than the current
     * one — the trigger for the "Resurfaced from Depth N" marker.
     */
    public function resurfacedAt(int $currentDepth): bool
    {
        return $this->first_seen_depth < $currentDepth;
    }

    protected function casts(): array
    {
        return [
            'first_seen_depth' => 'integer',
            'last_seen_depth' => 'integer',
            'demonstrations' => 'integer',
            'misconceptions' => 'integer',
            'reviewed_at' => 'datetime',
            'mastered_at' => 'datetime',
        ];
    }
}
