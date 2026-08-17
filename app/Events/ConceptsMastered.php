<?php

namespace App\Events;

use App\Models\Concept;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Support\Collection;

/**
 * Fired when graded evidence carries one or more concepts over the mastery
 * threshold for the first time.
 *
 * Separate from LayerCompleted because the two genuinely come apart: a learner
 * can demonstrate one concept solidly while missing another, failing the
 * checkpoint and still having earned something real.
 */
class ConceptsMastered
{
    use Dispatchable;

    /**
     * @param  Collection<int, Concept>  $concepts
     */
    public function __construct(
        public readonly User $user,
        public readonly Conversation $conversation,
        public readonly Collection $concepts,
    ) {}
}
