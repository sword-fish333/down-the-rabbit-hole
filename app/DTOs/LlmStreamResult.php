<?php

namespace App\DTOs;

/**
 * The assembled outcome of a streamed LLM turn, returned once the stream closes.
 */
class LlmStreamResult
{
    public function __construct(
        public readonly string $text,
        public readonly string $model,
        public readonly int $inputTokens = 0,
        public readonly int $outputTokens = 0,
        public readonly int $cacheReadTokens = 0,
        public readonly ?string $stopReason = null,
    ) {}
}
