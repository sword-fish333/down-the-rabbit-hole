<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single turn in a descent. Only real user/assistant turns are stored — the
 * frozen system prompt and the per-turn control directive are never persisted.
 */
#[Fillable([
    'conversation_id',
    'role',
    'content',
    'depth',
    'phase',
    'model',
    'input_tokens',
    'output_tokens',
    'cache_read_tokens',
])]
class Message extends Model
{
    public const string ROLE_USER = 'user';

    public const string ROLE_ASSISTANT = 'assistant';

    public const array ROLES = [self::ROLE_USER, self::ROLE_ASSISTANT];

    public const string PHASE_TEACH = 'teach';

    /** A layer posed as its checkpoint alone — asked before it is ever taught. */
    public const string PHASE_QUESTION = 'question';

    public const string PHASE_GRADE = 'grade';

    /** The turns that open a layer, either of which leaves a checkpoint waiting. */
    public const array OPENING_PHASES = [self::PHASE_TEACH, self::PHASE_QUESTION];

    public const array PHASES = [self::PHASE_TEACH, self::PHASE_QUESTION, self::PHASE_GRADE];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    protected function casts(): array
    {
        return [
            'depth' => 'integer',
            'input_tokens' => 'integer',
            'output_tokens' => 'integer',
            'cache_read_tokens' => 'integer',
        ];
    }
}
