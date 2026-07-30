<?php

namespace App\Services\Llm;

use App\Contracts\LlmClient;
use App\DTOs\LlmStreamResult;
use App\DTOs\TurnPlan;
use App\Models\Message;
use Illuminate\Support\Facades\Http;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

/**
 * Talks to Gemini's streamGenerateContent endpoint over SSE. Same seam as
 * AnthropicClient — CHAT_PROVIDER picks which one is bound.
 *
 * Three things differ from Anthropic and drive the mapping below: the system
 * prompt travels in `systemInstruction`, the assistant role is called `model`,
 * and usage arrives as a running total on every frame instead of in a header
 * event. Prompt caching is implicit (no cache_control breakpoint to send).
 */
class GeminiClient implements LlmClient
{
    use ReadsSseStream;

    public function streamTurn(TurnPlan $plan, callable $onText): LlmStreamResult
    {
        $response = Http::withHeaders([
            'x-goog-api-key' => config('services.gemini.key'),
            'content-type' => 'application/json',
        ])
            ->withOptions(['stream' => true])
            ->timeout(120)
            ->post($this->endpoint($plan->model), $this->payload($plan));

        if ($response->failed()) {
            $body = (string) $response->toPsrResponse()->getBody();
            fullLog("Gemini request failed [{$response->status()}]: {$body}");
            throw new RuntimeException(__('frontend.chat.llm-error'));
        }

        return $this->consume($response->toPsrResponse()->getBody(), $plan->model, $onText);
    }

    private function endpoint(string $model): string
    {
        return rtrim(config('services.gemini.base_url'), '/')
            ."/v1beta/models/{$model}:streamGenerateContent?alt=sse";
    }

    private function payload(TurnPlan $plan): array
    {
        return [
            'systemInstruction' => ['parts' => [['text' => $plan->system]]],
            'contents' => array_map(fn (array $message) => [
                'role' => $message['role'] === Message::ROLE_ASSISTANT ? 'model' : 'user',
                'parts' => [['text' => $message['content']]],
            ], $plan->messages),
            'generationConfig' => ['maxOutputTokens' => $plan->maxTokens],
        ];
    }

    /**
     * Assemble the streamed candidate, forwarding each text part to $onText.
     *
     * @param  callable(string): void  $onText
     */
    private function consume(StreamInterface $body, string $model, callable $onText): LlmStreamResult
    {
        $text = '';
        $input = $output = $cacheRead = 0;
        $stopReason = null;

        foreach ($this->sseFrames($body) as $frame) {
            $candidate = $frame['candidates'][0] ?? [];

            foreach ($candidate['content']['parts'] ?? [] as $part) {
                // Reasoning parts are flagged `thought` — they are not the answer.
                if (($part['thought'] ?? false) || ! isset($part['text'])) {
                    continue;
                }

                $text .= $part['text'];
                $onText($part['text']);
            }

            $stopReason = $candidate['finishReason'] ?? $stopReason;

            $usage = $frame['usageMetadata'] ?? [];
            $input = (int) ($usage['promptTokenCount'] ?? $input);
            $output = (int) ($usage['candidatesTokenCount'] ?? $output);
            $cacheRead = (int) ($usage['cachedContentTokenCount'] ?? $cacheRead);
        }

        return new LlmStreamResult($text, $model, $input, $output, $cacheRead, $stopReason);
    }
}
