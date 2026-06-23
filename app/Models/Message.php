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

    public const string PHASE_GRADE = 'grade';

    public const array PHASES = [self::PHASE_TEACH, self::PHASE_GRADE];

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
