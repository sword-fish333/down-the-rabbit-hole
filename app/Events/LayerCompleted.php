<?php

namespace App\Events;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired when a learner passes a layer's checkpoint. The seam for all
 * progress-driven mechanics: add a listener, never touch the descent flow.
 */
class LayerCompleted
{
    use Dispatchable;

    public function __construct(
        public readonly User $user,
        public readonly Conversation $conversation,
        public readonly int $depth,
    ) {}
}
