<?php

namespace App\Services\Llm;

use App\Contracts\LlmClient;
use App\DTOs\Llm\ContextSummary;
use App\DTOs\Llm\ContextSummaryRequest;
use App\DTOs\Llm\GradingRequest;
use App\DTOs\Llm\GradingResult;
use App\DTOs\Llm\LlmStream;
use App\DTOs\Llm\LlmUsage;
use App\DTOs\Llm\TeachingRequest;
use App\Models\Message;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

/**
 * Gemini behind the same LlmClient seam — CHAT_PROVIDER picks which client is
 * bound. Three things differ from Anthropic and drive the mapping below: the
 * system prompt travels in `systemInstruction`, the assistant role is called
 * `model`, and usage arrives as a running total on every frame rather than in a
 * header event. Prompt caching is implicit (no breakpoint to send).
 *
 * Structured output is `responseMimeType: application/json` +
 * `responseJsonSchema` — the full-JSON-Schema field, which (unlike the older
 * OpenAPI-subset `responseSchema`) accepts `additionalProperties: false`.
 */
class GeminiClient implements LlmClient
{
    use DecodesJsonPayload, ReadsSseStream;

    public function __construct(private readonly GradingSchema $schemas) {}

    public function streamTeachingTurn(TeachingRequest $request): LlmStream
    {
        $body = $this->stream($request->model, [
            'systemInstruction' => ['parts' => [['text' => $request->system]]],
            'contents' => $this->contents($request->messages),
            'generationConfig' => ['maxOutputTokens' => $request->maxTokens],
        ]);

        $usage = new LlmUsage;

        return new LlmStream(
            frames: function () use ($body, &$usage) {
                $input = $output = $cacheRead = 0;
                $stopReason = null;

                foreach ($this->sseFrames($body) as $frame) {
                    $candidate = $frame['candidates'][0] ?? [];

                    foreach ($candidate['content']['parts'] ?? [] as $part) {
                        // Reasoning parts are flagged `thought` — not the answer.
                        if (($part['thought'] ?? false) || ! isset($part['text'])) {
                            continue;
                        }

                        yield $part['text'];
                    }

                    $stopReason = $candidate['finishReason'] ?? $stopReason;

                    $metadata = $frame['usageMetadata'] ?? [];
                    $input = (int) ($metadata['promptTokenCount'] ?? $input);
                    $output = (int) ($metadata['candidatesTokenCount'] ?? $output);
                    $cacheRead = (int) ($metadata['cachedContentTokenCount'] ?? $cacheRead);
                }

                $usage = new LlmUsage($input, $output, $cacheRead, $stopReason);
            },
            // NB: by reference — an arrow fn would capture the empty initial
            // value and every turn would record zero tokens.
            usageResolver: function () use (&$usage) {
                return $usage;
            },
            model: $request->model,
        );
    }

    public function gradeCheckpoint(GradingRequest $request): GradingResult
    {
        $payload = $this->structured(
            model: $request->model,
            system: $request->system,
            prompt: GradingPrompt::checkpoint($request),
            schema: $this->schemas->grading(),
            maxTokens: $request->maxTokens,
        );

        return GradingResult::fromArray($payload['data'], $request->model, $payload['usage']);
    }

    public function summarizeContext(ContextSummaryRequest $request): ContextSummary
    {
        $payload = $this->structured(
            model: $request->model,
            system: GradingPrompt::summarySystem(),
            prompt: GradingPrompt::summary($request),
            schema: $this->schemas->contextSummary(),
            maxTokens: $request->maxTokens,
        );

        return ContextSummary::fromArray($payload['data'], $request->model, $payload['usage']);
    }

    /**
     * One non-streaming call constrained to $schema.
     *
     * @param  array<string, mixed>  $schema
     * @return array{data: array<string, mixed>, usage: LlmUsage}
     */
    private function structured(string $model, string $system, string $prompt, array $schema, int $maxTokens): array
    {
        $body = $this->generate($model, [
            'systemInstruction' => ['parts' => [['text' => $system]]],
            'contents' => [['role' => 'user', 'parts' => [['text' => $prompt]]]],
            'generationConfig' => [
                'maxOutputTokens' => $maxTokens,
                'responseMimeType' => 'application/json',
                'responseJsonSchema' => $schema,
            ],
        ]);

        $text = collect($body['candidates'][0]['content']['parts'] ?? [])
            ->reject(fn (array $part) => $part['thought'] ?? false)
            ->pluck('text')
            ->implode('');

        return [
            'data' => $this->decodePayload($text),
            'usage' => new LlmUsage(
                inputTokens: (int) ($body['usageMetadata']['promptTokenCount'] ?? 0),
                outputTokens: (int) ($body['usageMetadata']['candidatesTokenCount'] ?? 0),
                cacheReadTokens: (int) ($body['usageMetadata']['cachedContentTokenCount'] ?? 0),
                stopReason: $body['candidates'][0]['finishReason'] ?? null,
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function stream(string $model, array $payload): StreamInterface
    {
        $response = $this->request(stream: true)
            ->post($this->endpoint($model, 'streamGenerateContent').'?alt=sse', $payload);

        $this->assertOk($response);

        return $response->toPsrResponse()->getBody();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function generate(string $model, array $payload): array
    {
        $response = $this->request(stream: false)
            ->post($this->endpoint($model, 'generateContent'), $payload);

        $this->assertOk($response);

        return $response->json();
    }

    private function request(bool $stream): PendingRequest
    {
        return Http::withHeaders([
            'x-goog-api-key' => config('services.gemini.key'),
            'content-type' => 'application/json',
        ])->withOptions(['stream' => $stream])->timeout(120);
    }

    private function assertOk(Response $response): void
    {
        if ($response->failed()) {
            $body = (string) $response->toPsrResponse()->getBody();
            fullLog("Gemini request failed [{$response->status()}]: {$body}");
            throw new RuntimeException(__('frontend.chat.llm-error'));
        }
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     * @return array<int, array<string, mixed>>
     */
    private function contents(array $messages): array
    {
        return array_map(fn (array $message) => [
            'role' => $message['role'] === Message::ROLE_ASSISTANT ? 'model' : 'user',
            'parts' => [['text' => $message['content']]],
        ], $messages);
    }

    private function endpoint(string $model, string $method): string
    {
        return rtrim(config('services.gemini.base_url'), '/')."/v1beta/models/{$model}:{$method}";
    }
}
