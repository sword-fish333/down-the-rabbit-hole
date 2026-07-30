<?php

namespace Tests\Feature;

use App\DTOs\Llm\ContextSummaryRequest;
use App\DTOs\Llm\GradingRequest;
use App\DTOs\Llm\TeachingRequest;
use App\Models\CheckpointAttempt;
use App\Models\Message;
use App\Services\Llm\GeminiClient;
use App\Services\Llm\GradingSchema;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Pins the Gemini wire mapping: the role rename, the systemInstruction block,
 * SSE frame parsing (including dropping `thought` parts), and — the important
 * one — that grading is sent as a schema-constrained JSON request rather than
 * asked for in prose. No key or network needed; both calls are faked.
 */
class GeminiClientTest extends TestCase
{
    private function client(): GeminiClient
    {
        return new GeminiClient(new GradingSchema);
    }

    public function test_it_maps_the_teaching_turn_and_assembles_the_stream(): void
    {
        Http::fake(['*' => Http::response($this->sseBody(), 200)]);

        $stream = $this->client()->streamTeachingTurn(new TeachingRequest(
            model: 'gemini-3.5-flash-lite',
            system: 'SYSTEM',
            messages: [
                ['role' => Message::ROLE_USER, 'content' => 'Pure functions'],
                ['role' => Message::ROLE_ASSISTANT, 'content' => 'Layer 0.'],
            ],
            depth: 0,
            maxTokens: 2048,
        ));

        $deltas = [];
        foreach ($stream as $delta) {
            $deltas[] = $delta;
        }

        // The `thought` part is reasoning, not the answer — it never reaches the learner.
        $this->assertSame(['Hello', ' world'], $deltas);
        $this->assertSame('Hello world', $stream->text());
        $this->assertTrue($stream->isComplete());
        $this->assertSame(11, $stream->usage()->inputTokens);
        $this->assertSame(4, $stream->usage()->outputTokens);
        $this->assertSame('STOP', $stream->usage()->stopReason);

        Http::assertSent(function (Request $request) {
            $body = $request->data();

            return str_contains($request->url(), '/v1beta/models/gemini-3.5-flash-lite:streamGenerateContent?alt=sse')
                && $body['systemInstruction']['parts'][0]['text'] === 'SYSTEM'
                && $body['contents'][0]['role'] === 'user'
                && $body['contents'][1]['role'] === 'model' // assistant is called "model" here
                && $body['generationConfig']['maxOutputTokens'] === 2048;
        });
    }

    public function test_grading_is_a_schema_constrained_json_request(): void
    {
        Http::fake(['*' => Http::response($this->gradingBody(), 200)]);

        $result = $this->client()->gradeCheckpoint(new GradingRequest(
            model: 'gemini-3.5-flash-lite',
            system: 'SYSTEM',
            subject: 'Pure functions',
            checkpoint: 'Explain it back.',
            answer: 'Same input, same output, no side effects.',
            depth: 0,
            maxTokens: 1024,
            selfRating: 80,
        ));

        $this->assertSame(CheckpointAttempt::VERDICT_PASS, $result->verdict);
        $this->assertSame(86, $result->score);
        $this->assertSame(['Referential transparency'], $result->demonstratedConcepts);
        $this->assertSame(CheckpointAttempt::ACTION_DESCEND, $result->recommendedAction);

        Http::assertSent(function (Request $request) {
            $config = $request->data()['generationConfig'];

            // This is what replaced the trailing Markdown control block: the
            // provider is constrained to the schema, so the verdict is
            // well-formed by construction rather than by hope.
            return str_contains($request->url(), ':generateContent')
                && $config['responseMimeType'] === 'application/json'
                && $config['responseJsonSchema']['properties']['verdict']['enum'] === CheckpointAttempt::VERDICTS
                && $config['responseJsonSchema']['additionalProperties'] === false;
        });
    }

    public function test_a_non_json_grading_response_degrades_to_a_safe_retry(): void
    {
        // A provider that ignores the schema must never cost a learner a layer.
        Http::fake(['*' => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => 'I could not comply.']]]]],
        ], 200)]);

        $result = $this->client()->gradeCheckpoint(new GradingRequest(
            model: 'gemini-3.5-flash-lite',
            system: 'SYSTEM',
            subject: 'Pure functions',
            checkpoint: 'Explain it back.',
            answer: 'an answer',
            depth: 0,
            maxTokens: 1024,
        ));

        $this->assertSame(CheckpointAttempt::VERDICT_RETRY, $result->verdict);
        $this->assertSame(0, $result->score);
    }

    public function test_it_summarizes_the_context(): void
    {
        Http::fake(['*' => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => json_encode([
                'summary' => 'Covered purity and side effects.',
                'covered_concepts' => ['Purity'],
            ])]]]]],
        ], 200)]);

        $summary = $this->client()->summarizeContext(new ContextSummaryRequest(
            model: 'gemini-3.5-flash-lite',
            subject: 'Pure functions',
            messages: [['role' => Message::ROLE_USER, 'content' => 'Pure functions']],
            depth: 2,
            maxTokens: 512,
        ));

        $this->assertSame('Covered purity and side effects.', $summary->summary);
        $this->assertSame(['Purity'], $summary->coveredConcepts);
        $this->assertFalse($summary->isEmpty());
    }

    private function sseBody(): string
    {
        $frames = [
            ['candidates' => [['content' => ['parts' => [['text' => 'weighing it up', 'thought' => true]]]]]],
            ['candidates' => [['content' => ['parts' => [['text' => 'Hello']]]]],
                'usageMetadata' => ['promptTokenCount' => 11]],
            ['candidates' => [['content' => ['parts' => [['text' => ' world']]], 'finishReason' => 'STOP']],
                'usageMetadata' => ['promptTokenCount' => 11, 'candidatesTokenCount' => 4]],
        ];

        return collect($frames)->map(fn (array $frame) => 'data: '.json_encode($frame)."\n\n")->implode('');
    }

    /**
     * @return array<string, mixed>
     */
    private function gradingBody(): array
    {
        return [
            'candidates' => [[
                'content' => ['parts' => [['text' => json_encode([
                    'verdict' => 'pass',
                    'score' => 86,
                    'confidence' => 91,
                    'criteria' => [['name' => 'Named determinism', 'met' => true, 'note' => 'Stated plainly.']],
                    'demonstrated_concepts' => ['Referential transparency'],
                    'missing_concepts' => [],
                    'misconceptions' => [],
                    'feedback' => 'That is it.',
                    'recommended_action' => 'descend',
                ])]]],
                'finishReason' => 'STOP',
            ]],
            'usageMetadata' => ['promptTokenCount' => 200, 'candidatesTokenCount' => 90],
        ];
    }
}
