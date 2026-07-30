<?php

namespace App\DTOs\Llm;

/**
 * Ask the provider to fold the turns that fell out of the verbatim window into
 * a short running summary, so a deep hole stays inside a cheap context.
 */
final class ContextSummaryRequest
{
    /**
     * @param  array<int, array{role: string, content: string}>  $messages  oldest first
     */
    public function __construct(
        public readonly string $model,
        public readonly string $subject,
        public readonly array $messages,
        public readonly int $depth,
        public readonly int $maxTokens,
        /** The summary so far — the new one extends rather than replaces it. */
        public readonly ?string $previousSummary = null,
    ) {}
}
