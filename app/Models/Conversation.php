<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * One subject: a single conversation with a measurable depth. The status is the
 * descent's state machine — see App\Services\Chat\DescentService.
 *
 * The model keeps its `Conversation` name because that is what it is at the
 * data layer; everything the learner sees calls it a *subject*.
 */
#[Fillable([
    'user_id',
    'folder_id',
    'learning_mode_id',
    'approach',
    'subject',
    'locale',
    'title',
    'current_depth',
    'status',
    'summary',
    'message_count',
    'surfaced_at',
    'share_token',
    'shared_at',
])]
class Conversation extends Model
{
    /**
     * How a layer opens.
     *
     * `guided` teaches the layer and then asks for proof. `question` poses the
     * checkpoint cold — the learner answers from what they already know, or goes
     * and finds out — and the layer is taught afterwards only if they ask for it.
     * Retrieval before instruction is the stronger way to learn; being made to
     * do it is the faster way to quit. So it is a choice, and `guided` is the
     * default.
     */
    public const string APPROACH_GUIDED = 'guided';

    public const string APPROACH_QUESTION = 'question';

    public const array APPROACHES = [self::APPROACH_GUIDED, self::APPROACH_QUESTION];

    public const string STATUS_EXPLORING = 'exploring';

    public const string STATUS_CHECKPOINT_PENDING = 'checkpoint_pending';

    public const string STATUS_SURFACED = 'surfaced';

    public const array STATUSES = [
        self::STATUS_EXPLORING,
        self::STATUS_CHECKPOINT_PENDING,
        self::STATUS_SURFACED,
    ];

    /** Library filters. `all` is the absence of a filter, so it has no clause. */
    public const string FILTER_ALL = 'all';

    public const string FILTER_ACTIVE = 'active';

    public const string FILTER_SURFACED = 'surfaced';

    public const string FILTER_SHARED = 'shared';

    public const array FILTERS = [
        self::FILTER_ALL,
        self::FILTER_ACTIVE,
        self::FILTER_SURFACED,
        self::FILTER_SHARED,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(SubjectFolder::class, 'folder_id');
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

    /** The web pages this subject is grounded in, if any. */
    public function sources(): HasMany
    {
        return $this->hasMany(Source::class);
    }

    #[Scope]
    protected function ownedBy(Builder $query, ?int $userId): void
    {
        $query->where('user_id', $userId);
    }

    /**
     * Free-text search over what the learner would actually recall: the subject
     * they typed and the short title derived from it.
     *
     * The wildcards in the term are escaped, and the ESCAPE clause is stated
     * explicitly — SQLite has no default escape character, so without it a
     * search for "50%" silently matches everything.
     */
    #[Scope]
    protected function matching(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $term).'%';
        $escape = " ESCAPE '\\'";

        $query->where(fn (Builder $inner) => $inner
            ->whereRaw("subject LIKE ?{$escape}", [$like])
            ->orWhereRaw("title LIKE ?{$escape}", [$like]));
    }

    /**
     * Deepest descent first.
     *
     * `current_depth` is the layer they are standing on, which is also the count
     * of layers they cleared to get there — except at the bottom, where it stops
     * advancing so the depth rail still has a node to point at. The `+ 1` puts a
     * completed subject back where it belongs.
     */
    #[Scope]
    protected function deepestFirst(Builder $query): void
    {
        $query->orderByRaw('(current_depth + case when status = ? then 1 else 0 end) desc', [self::STATUS_SURFACED]);
    }

    #[Scope]
    protected function filtered(Builder $query, ?string $filter): void
    {
        match ($filter) {
            self::FILTER_ACTIVE => $query->where('status', '!=', self::STATUS_SURFACED),
            self::FILTER_SURFACED => $query->where('status', self::STATUS_SURFACED),
            self::FILTER_SHARED => $query->whereNotNull('share_token'),
            default => null,
        };
    }

    public function isCheckpointPending(): bool
    {
        return $this->status === self::STATUS_CHECKPOINT_PENDING;
    }

    /** True when a new layer arrives as its checkpoint rather than as a lesson. */
    public function opensWithQuestion(): bool
    {
        return $this->approach === self::APPROACH_QUESTION;
    }

    public function isSurfaced(): bool
    {
        return $this->status === self::STATUS_SURFACED;
    }

    public function isShared(): bool
    {
        return $this->share_token !== null;
    }

    /**
     * How many layers were actually proven here — the number the product quotes
     * ("7 layers deep on Stoicism"). See the `deepestFirst` scope for why the
     * bottom layer needs adding back on.
     */
    public function layersCleared(): int
    {
        return $this->isSurfaced() ? $this->current_depth + 1 : $this->current_depth;
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

    /**
     * The language this subject is taught in, named in English ("Romanian") —
     * that is the form a model resolves most reliably, and it is the only place
     * the locale registry is turned into prompt text.
     */
    public function language(): string
    {
        return config("platform.locales.{$this->locale}.prompt")
            ?? config('platform.locales.'.config('app.fallback_locale').'.prompt', 'English');
    }

    /**
     * Mint the capability that makes this subject readable by link, or hand back
     * the one already minted — sharing twice should not invalidate a link the
     * learner has already sent someone.
     */
    public function share(): string
    {
        if (! $this->isShared()) {
            $this->update(['share_token' => Str::random(32), 'shared_at' => now()]);
        }

        return $this->share_token;
    }

    public function unshare(): void
    {
        $this->update(['share_token' => null, 'shared_at' => null]);
    }

    protected function casts(): array
    {
        return [
            'current_depth' => 'integer',
            'message_count' => 'integer',
            'surfaced_at' => 'datetime',
            'shared_at' => 'datetime',
        ];
    }
}
