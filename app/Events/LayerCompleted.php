<?php

namespace App\Events;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired when a learner passes a layer's checkpoint. The seam for all
 * progress-driven mechanics: add a listener, never touch the descent flow.
 *
 * It carries the *facts* of the clear rather than its consequences — how deep,
 * whether it took more than one attempt, whether it was the last layer — and
 * leaves listeners to decide what any of that is worth. Adding a reward is then
 * a listener, not a change to the descent.
 */
class LayerCompleted
{
    use Dispatchable;

    public function __construct(
        public readonly User $user,
        public readonly Conversation $conversation,
        public readonly int $depth,
        public readonly bool $firstTry = false,
        public readonly bool $surfaced = false,
    ) {}
}
