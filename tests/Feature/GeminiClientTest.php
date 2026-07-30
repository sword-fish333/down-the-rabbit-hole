<?php

namespace Tests\Feature;

use App\DTOs\TurnPlan;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\Llm\GeminiClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Pins the Gemini wire mapping — the role rename, the systemInstruction block,
 * and the SSE frame parsing (including dropping `thought` parts). No key or
 * network needed; the stream is faked.
 */
class GeminiClientTest extends TestCase
{
    public function test_it_maps_the_turn_and_assembles_the_streamed_answer(): void
    {
        Http::fake(['*' => Http::response($this->sseBody(), 200)]);

        $deltas = [];
        $result = (new GeminiClient)->streamTurn(
            $this->plan(),
            function (string $delta) use (&$deltas) {
                $deltas[] = $delta;
            },
        );

        $this->assertSame(['Hello', ' world'], $deltas); // the `thought` part never reaches the learner
        $this->assertSame('Hello world', $result->text);
        $this->assertSame(11, $result->inputTokens);
        $this->assertSame(4, $result->outputTokens);
        $this->assertSame('STOP', $result->stopReason);

        Http::assertSent(function (Request $request) {
            $body = $request->data();

            return str_contains($request->url(), '/v1beta/models/gemini-3.5-flash-lite:streamGenerateContent?alt=sse')
                && $body['systemInstruction']['parts'][0]['text'] === 'SYSTEM'
                && $body['contents'][0]['role'] === 'user'
                && $body['contents'][1]['role'] === 'model' // assistant is called "model" here
                && $body['generationConfig']['maxOutputTokens'] === 2048;
        });
    }

    private function plan(): TurnPlan
    {
        return new TurnPlan(
            conversation: new Conversation,
            model: 'gemini-3.5-flash-lite',
            phase: Message::PHASE_TEACH,
            depth: 0,
            system: 'SYSTEM',
            messages: [
                ['role' => Message::ROLE_USER, 'content' => 'Pure functions'],
                ['role' => Message::ROLE_ASSISTANT, 'content' => 'Layer 0.'],
            ],
            maxTokens: 2048,
        );
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
}
