<?php

namespace App\Contracts;

use App\DTOs\Llm\ContextSummary;
use App\DTOs\Llm\ContextSummaryRequest;
use App\DTOs\Llm\GradingRequest;
use App\DTOs\Llm\GradingResult;
use App\DTOs\Llm\LlmStream;
use App\DTOs\Llm\TeachingRequest;

/**
 * The single seam over the LLM provider. Three verbs, because the descent only
 * ever needs three things from a model:
 *
 *  - teach a layer          → streamed prose, rendered as it arrives
 *  - grade a checkpoint     → a strict JSON-schema structured output
 *  - summarize the context  → fold old turns into a short running summary
 *
 * Grading deliberately does NOT stream and does NOT parse free text: every
 * provider we support enforces a JSON Schema server-side, so the result object
 * is well-formed by construction instead of scraped out of a Markdown fence.
 */
interface LlmClient
{
    public function streamTeachingTurn(TeachingRequest $request): LlmStream;

    public function gradeCheckpoint(GradingRequest $request): GradingResult;

    public function summarizeContext(ContextSummaryRequest $request): ContextSummary;
}
