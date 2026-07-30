<?php

namespace App\DTOs\Llm;

/**
 * One checkpoint sent for grading. The provider is asked to return
 * {@see GradingResult} against a strict JSON Schema — no prose to parse.
 */
final class GradingRequest
{
    public function __construct(
        public readonly string $model,
        public readonly string $system,
        public readonly string $subject,
        public readonly string $checkpoint,
        public readonly string $answer,
        public readonly int $depth,
        public readonly int $maxTokens,
        /** The learner's own confidence, 0..100, or null when they skipped it. */
        public readonly ?int $selfRating = null,
    ) {}
}
