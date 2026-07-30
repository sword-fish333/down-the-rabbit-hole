<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One graded proof-of-understanding. Populated from a strict JSON-schema
 * structured output (App\DTOs\Llm\GradingResult), so every field is reliable —
 * there is no free-text parsing anywhere in the grading path.
 */
#[Fillable([
    'conversation_id',
    'message_id',
    'depth',
    'verdict',
    'score',
    'confidence',
    'self_rating',
    'criteria',
    'demonstrated_concepts',
    'missing_concepts',
    'misconceptions',
    'feedback',
    'recommended_action',
    'model',
])]
class CheckpointAttempt extends Model
{
    public const string VERDICT_PASS = 'pass';

    public const string VERDICT_PARTIAL = 'partial';

    public const string VERDICT_RETRY = 'retry';

    public const array VERDICTS = [self::VERDICT_PASS, self::VERDICT_PARTIAL, self::VERDICT_RETRY];

    public const string ACTION_DESCEND = 'descend';

    public const string ACTION_RETRY = 'retry';

    public const string ACTION_REVIEW = 'review';

    public const array ACTIONS = [self::ACTION_DESCEND, self::ACTION_RETRY, self::ACTION_REVIEW];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    public function passed(): bool
    {
        return $this->verdict === self::VERDICT_PASS;
    }

    /**
     * A wrong answer and a misconception are different failures and deserve
     * different copy — this separates them for the UI.
     */
    public function hasMisconception(): bool
    {
        return filled($this->misconceptions);
    }

    /**
     * Gap between what the learner thought they knew and what they showed.
     * Null when they skipped the confidence step. Positive = overconfident.
     */
    public function calibrationGap(): ?int
    {
        return $this->self_rating === null ? null : $this->self_rating - $this->score;
    }

    protected function casts(): array
    {
        return [
            'depth' => 'integer',
            'score' => 'integer',
            'confidence' => 'integer',
            'self_rating' => 'integer',
            'criteria' => 'array',
            'demonstrated_concepts' => 'array',
            'missing_concepts' => 'array',
            'misconceptions' => 'array',
        ];
    }
}
