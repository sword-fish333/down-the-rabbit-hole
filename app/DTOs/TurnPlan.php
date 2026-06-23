<?php

namespace App\DTOs;

use App\Models\Conversation;

/**
 * Everything the LLM needs for one assistant turn in a descent — pure data, no
 * behaviour. Built by DescentService, consumed by the LlmClient.
 */
class TurnPlan
{
    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     */
    public function __construct(
        public readonly Conversation $conversation,
        public readonly string $model,
        public readonly string $phase,
        public readonly int $depth,
        public readonly string $system,
        public readonly array $messages,
        public readonly int $maxTokens,
    ) {}
}
