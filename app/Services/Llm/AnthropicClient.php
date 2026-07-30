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
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

/**
 * Anthropic Messages API behind the LlmClient seam (a thin Guzzle wrapper, no
 * SDK dependency — matching how the rest of this app talks to HTTP services).
 *
 * Two things are worth knowing:
 *  - the frozen system prompt carries a `cache_control` breakpoint, so its
 *    prefix is cached across turns (verify via `cache_read_input_tokens`);
 *  - grading and summarising use `output_config.format` (structured outputs) so
 *    the model is constrained to the JSON Schema rather than asked politely.
 */
class AnthropicClient implements LlmClient
{
    use DecodesJsonPayload, ReadsSseStream;

    public function __construct(private readonly GradingSchema $schemas) {}

    public function streamTeachingTurn(TeachingRequest $request): LlmStream
    {
        $body = $this->send([
            'model' => $request->model,
            'max_tokens' => $request->maxTokens,
            'stream' => true,
            'system' => $this->cachedSystem($request->system),
            'messages' => $request->messages,
        ], stream: true);

        $usage = new LlmUsage;

        return new LlmStream(
            frames: function () use ($body, &$usage) {
                $input = $output = $cacheRead = 0;
                $stopReason = null;

                foreach ($this->sseFrames($body) as $frame) {
                    switch ($frame['type'] ?? null) {
                        case 'content_block_delta':
                            yield $frame['delta']['text'] ?? '';
                            break;

                        case 'message_start':
                            $input = (int) ($frame['message']['usage']['input_tokens'] ?? 0);
                            $cacheRead = (int) ($frame['message']['usage']['cache_read_input_tokens'] ?? 0);
                            break;

                        case 'message_delta':
                            $output = (int) ($frame['usage']['output_tokens'] ?? $output);
                            $stopReason = $frame['delta']['stop_reason'] ?? $stopReason;
                            break;
                    }
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
        $body = $this->send([
            'model' => $model,
            'max_tokens' => $maxTokens,
            'system' => $this->cachedSystem($system),
            'messages' => [['role' => 'user', 'content' => $prompt]],
            'output_config' => ['format' => ['type' => 'json_schema', 'schema' => $schema]],
        ], stream: false);

        $text = collect($body['content'] ?? [])
            ->where('type', 'text')
            ->pluck('text')
            ->implode('');

        return [
            'data' => $this->decodePayload($text),
            'usage' => new LlmUsage(
                inputTokens: (int) ($body['usage']['input_tokens'] ?? 0),
                outputTokens: (int) ($body['usage']['output_tokens'] ?? 0),
                cacheReadTokens: (int) ($body['usage']['cache_read_input_tokens'] ?? 0),
                stopReason: $body['stop_reason'] ?? null,
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return ($stream is true ? StreamInterface : array<string, mixed>)
     */
    private function send(array $payload, bool $stream): StreamInterface|array
    {
        if ($thinking = config('platform.chat.thinking')) {
            $payload['thinking'] = ['type' => $thinking];
        }

        $response = $this->request($stream)->post($this->endpoint(), $payload);

        if ($response->failed()) {
            $body = (string) $response->toPsrResponse()->getBody();
            fullLog("Anthropic request failed [{$response->status()}]: {$body}");
            throw new RuntimeException(__('frontend.chat.llm-error'));
        }

        return $stream ? $response->toPsrResponse()->getBody() : $response->json();
    }

    private function request(bool $stream): PendingRequest
    {
        return Http::withHeaders([
            'x-api-key' => config('services.anthropic.key'),
            'anthropic-version' => config('services.anthropic.version'),
            'content-type' => 'application/json',
        ])->withOptions(['stream' => $stream])->timeout(120);
    }

    /**
     * The system prompt is frozen, so a cache breakpoint on it pays for itself
     * from turn two onward.
     *
     * @return array<int, array<string, mixed>>
     */
    private function cachedSystem(string $system): array
    {
        return [[
            'type' => 'text',
            'text' => $system,
            'cache_control' => ['type' => 'ephemeral'],
        ]];
    }

    private function endpoint(): string
    {
        return rtrim(config('services.anthropic.base_url'), '/').'/v1/messages';
    }
}
