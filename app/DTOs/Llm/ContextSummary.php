<?php

namespace App\DTOs\Llm;

/**
 * A compacted view of the turns that fell out of the verbatim window.
 */
final class ContextSummary
{
    /**
     * @param  array<int, string>  $coveredConcepts
     */
    public function __construct(
        public readonly string $summary,
        public readonly array $coveredConcepts = [],
        public readonly string $model = '',
        public readonly LlmUsage $usage = new LlmUsage,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload, string $model = '', ?LlmUsage $usage = null): self
    {
        $concepts = is_array($payload['covered_concepts'] ?? null) ? $payload['covered_concepts'] : [];

        return new self(
            summary: trim((string) ($payload['summary'] ?? '')),
            coveredConcepts: array_values(array_filter(array_map(
                fn ($item) => is_string($item) ? trim($item) : '',
                $concepts,
            ))),
            model: $model,
            usage: $usage ?? new LlmUsage,
        );
    }

    public function isEmpty(): bool
    {
        return $this->summary === '';
    }
}
