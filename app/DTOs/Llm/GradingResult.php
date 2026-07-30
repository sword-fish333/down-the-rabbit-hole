<?php

namespace App\DTOs\Llm;

use App\Models\CheckpointAttempt;
use App\Services\Llm\GradingSchema;

/**
 * The graded verdict on one checkpoint. Mirrors the JSON Schema in
 * {@see GradingSchema}, which the provider enforces.
 */
final class GradingResult
{
    /**
     * @param  array<int, array{name: string, met: bool, note?: string}>  $criteria
     * @param  array<int, string>  $demonstratedConcepts
     * @param  array<int, string>  $missingConcepts
     * @param  array<int, array{concept: string, belief: string, correction: string}>  $misconceptions
     */
    public function __construct(
        public readonly string $verdict,
        public readonly int $score,
        public readonly int $confidence,
        public readonly array $criteria,
        public readonly array $demonstratedConcepts,
        public readonly array $missingConcepts,
        public readonly array $misconceptions,
        public readonly string $feedback,
        public readonly string $recommendedAction,
        public readonly string $model = '',
        public readonly LlmUsage $usage = new LlmUsage,
    ) {}

    /**
     * Build from the provider's decoded JSON, clamping every field to the shape
     * the app expects. The schema makes this near-redundant — it is the belt to
     * the schema's braces, and the single place a provider quirk can be absorbed.
     *
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload, string $model = '', ?LlmUsage $usage = null): self
    {
        $verdict = self::oneOf($payload['verdict'] ?? null, CheckpointAttempt::VERDICTS, CheckpointAttempt::VERDICT_RETRY);
        $action = self::oneOf($payload['recommended_action'] ?? null, CheckpointAttempt::ACTIONS, CheckpointAttempt::ACTION_RETRY);

        return new self(
            verdict: $verdict,
            score: self::percent($payload['score'] ?? 0),
            confidence: self::percent($payload['confidence'] ?? 0),
            criteria: self::listOfArrays($payload['criteria'] ?? []),
            demonstratedConcepts: self::listOfStrings($payload['demonstrated_concepts'] ?? []),
            missingConcepts: self::listOfStrings($payload['missing_concepts'] ?? []),
            misconceptions: self::listOfArrays($payload['misconceptions'] ?? []),
            feedback: trim((string) ($payload['feedback'] ?? '')),
            recommendedAction: $action,
            model: $model,
            usage: $usage ?? new LlmUsage,
        );
    }

    /**
     * The safe verdict for a call that failed outright (network, provider error).
     * Never punishes the learner: they keep their depth and are asked to retry.
     */
    public static function unavailable(string $feedback): self
    {
        return new self(
            verdict: CheckpointAttempt::VERDICT_RETRY,
            score: 0,
            confidence: 0,
            criteria: [],
            demonstratedConcepts: [],
            missingConcepts: [],
            misconceptions: [],
            feedback: $feedback,
            recommendedAction: CheckpointAttempt::ACTION_RETRY,
        );
    }

    public function passed(): bool
    {
        return $this->verdict === CheckpointAttempt::VERDICT_PASS;
    }

    /**
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        return [
            'verdict' => $this->verdict,
            'score' => $this->score,
            'confidence' => $this->confidence,
            'criteria' => $this->criteria,
            'demonstrated_concepts' => $this->demonstratedConcepts,
            'missing_concepts' => $this->missingConcepts,
            'misconceptions' => $this->misconceptions,
            'feedback' => $this->feedback,
            'recommended_action' => $this->recommendedAction,
            'model' => $this->model,
        ];
    }

    private static function percent(mixed $value): int
    {
        // Accept both 0..1 and 0..100 — providers differ on how they read "score".
        $number = is_numeric($value) ? (float) $value : 0.0;
        $scaled = $number > 0 && $number <= 1 ? $number * 100 : $number;

        return (int) max(0, min(100, round($scaled)));
    }

    /**
     * @param  array<int, string>  $allowed
     */
    private static function oneOf(mixed $value, array $allowed, string $fallback): string
    {
        return in_array($value, $allowed, true) ? $value : $fallback;
    }

    /**
     * @return array<int, string>
     */
    private static function listOfStrings(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn ($item) => is_string($item) ? trim($item) : '',
            $value,
        )));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function listOfArrays(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter($value, 'is_array'));
    }
}
