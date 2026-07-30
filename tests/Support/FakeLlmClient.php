<?php

namespace Tests\Support;

use App\Contracts\LlmClient;
use App\DTOs\Llm\ContextSummary;
use App\DTOs\Llm\ContextSummaryRequest;
use App\DTOs\Llm\GradingRequest;
use App\DTOs\Llm\GradingResult;
use App\DTOs\Llm\LlmStream;
use App\DTOs\Llm\LlmUsage;
use App\DTOs\Llm\TeachingRequest;
use App\Models\CheckpointAttempt;
use RuntimeException;

/**
 * Drives the descent offline. Every test that exercises the state machine binds
 * this in place of the real client, so no API key, network, or fixture recording
 * is involved anywhere in the suite.
 */
class FakeLlmClient implements LlmClient
{
    public ?TeachingRequest $lastTeachingRequest = null;

    public ?GradingRequest $lastGradingRequest = null;

    public int $teachCalls = 0;

    public function __construct(
        /** @var array<int, string> */
        public array $chunks = ["Here is the layer.\n\n", '**Checkpoint:** Explain it back.'],
        public string $verdict = CheckpointAttempt::VERDICT_PASS,
        public bool $gradeThrows = false,
        /** @var array<int, string> */
        public array $demonstrated = ['Pure functions'],
        /** @var array<int, array<string, string>> */
        public array $misconceptions = [],
    ) {}

    public function streamTeachingTurn(TeachingRequest $request): LlmStream
    {
        $this->lastTeachingRequest = $request;
        $this->teachCalls++;

        $chunks = $this->chunks;

        return new LlmStream(
            frames: function () use ($chunks) {
                foreach ($chunks as $chunk) {
                    yield $chunk;
                }
            },
            usageResolver: function () {
                return new LlmUsage(120, 40, 100, 'end_turn');
            },
            model: $request->model,
        );
    }

    public function gradeCheckpoint(GradingRequest $request): GradingResult
    {
        $this->lastGradingRequest = $request;

        if ($this->gradeThrows) {
            throw new RuntimeException('provider unavailable');
        }

        $passed = $this->verdict === CheckpointAttempt::VERDICT_PASS;

        return GradingResult::fromArray([
            'verdict' => $this->verdict,
            'score' => $passed ? 86 : 34,
            'confidence' => 91,
            'criteria' => [
                ['name' => 'Named the core idea', 'met' => $passed, 'note' => 'Stated in their own words.'],
            ],
            'demonstrated_concepts' => $passed ? $this->demonstrated : [],
            'missing_concepts' => $passed ? [] : $this->demonstrated,
            'misconceptions' => $this->misconceptions,
            'feedback' => $passed ? 'That is exactly it.' : 'Close — one piece is still missing.',
            'recommended_action' => $passed ? CheckpointAttempt::ACTION_DESCEND : CheckpointAttempt::ACTION_RETRY,
        ], $request->model, new LlmUsage(200, 90, 150));
    }

    public function summarizeContext(ContextSummaryRequest $request): ContextSummary
    {
        return ContextSummary::fromArray([
            'summary' => 'Covered the foundations of '.$request->subject.'.',
            'covered_concepts' => $this->demonstrated,
        ], $request->model);
    }
}
