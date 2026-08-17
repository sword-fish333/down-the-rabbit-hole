<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An immutable XP ledger entry. Append-only — balances are projections, never
 * mutate a row. New reward mechanics add new `type` values, not new tables.
 */
#[Fillable(['user_id', 'conversation_id', 'type', 'amount', 'depth'])]
class XpEvent extends Model
{
    public const string TYPE_LAYER_COMPLETED = 'layer_completed';

    /** Cleared without a failed attempt at that layer. */
    public const string TYPE_FIRST_TRY = 'first_try';

    /** A concept crossed into mastered — demonstrated, not asserted. */
    public const string TYPE_CONCEPT_MASTERED = 'concept_mastered';

    /** A subject carried all the way to the bottom. */
    public const string TYPE_SUBJECT_SURFACED = 'subject_surfaced';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'depth' => 'integer',
        ];
    }
}
