<?php

namespace App\DTOs\Llm;

/**
 * Token accounting for one provider call. Cache reads are tracked separately so
 * a broken prompt cache shows up in the data instead of only on the invoice.
 */
final class LlmUsage
{
    public function __construct(
        public readonly int $inputTokens = 0,
        public readonly int $outputTokens = 0,
        public readonly int $cacheReadTokens = 0,
        public readonly ?string $stopReason = null,
    ) {}
}
