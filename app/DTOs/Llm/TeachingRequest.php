<?php

namespace App\DTOs\Llm;

/**
 * Everything the provider needs to teach one layer. Pure data, no behaviour —
 * built by DescentService, consumed by an LlmClient.
 */
final class TeachingRequest
{
    /**
     * @param  array<int, array{role: string, content: string}>  $messages  oldest first
     */
    public function __construct(
        public readonly string $model,
        public readonly string $system,
        public readonly array $messages,
        public readonly int $depth,
        public readonly int $maxTokens,
    ) {}
}
