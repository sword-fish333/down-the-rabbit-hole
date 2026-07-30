<?php

namespace App\Services\Llm;

use App\Contracts\LlmClient;
use App\DTOs\LlmStreamResult;
use App\DTOs\TurnPlan;
use Illuminate\Support\Facades\Http;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

/**
 * Talks to Anthropic's Messages API over streaming HTTP (no SDK dependency — a
 * thin Guzzle wrapper behind the LlmClient seam). The frozen system prompt is
 * sent with a cache_control breakpoint so its prefix is cached across turns.
 */
class AnthropicClient implements LlmClient
{
    use ReadsSseStream;

    public function streamTurn(TurnPlan $plan, callable $onText): LlmStreamResult
    {
        $response = Http::withHeaders([
            'x-api-key' => config('services.anthropic.key'),
            'anthropic-version' => config('services.anthropic.version'),
            'content-type' => 'application/json',
        ])
            ->withOptions(['stream' => true])
            ->timeout(120)
            ->post($this->endpoint(), $this->payload($plan));

        if ($response->failed()) {
            $body = (string) $response->toPsrResponse()->getBody();
            fullLog("Anthropic request failed [{$response->status()}]: {$body}");
            throw new RuntimeException(__('frontend.chat.llm-error'));
        }

        return $this->consume($response->toPsrResponse()->getBody(), $plan->model, $onText);
    }

    private function endpoint(): string
    {
        return rtrim(config('services.anthropic.base_url'), '/').'/v1/messages';
    }

    private function payload(TurnPlan $plan): array
    {
        return [
            'model' => $plan->model,
            'max_tokens' => $plan->maxTokens,
            'stream' => true,
            'system' => [[
                'type' => 'text',
                'text' => $plan->system,
                'cache_control' => ['type' => 'ephemeral'],
            ]],
            'messages' => $plan->messages,
        ];
    }

    /**
     * Read the Server-Sent Events body, forwarding text deltas to $onText and
     * accumulating the full text + token usage.
     *
     * @param  callable(string): void  $onText
     */
    private function consume(StreamInterface $body, string $model, callable $onText): LlmStreamResult
    {
        $text = '';
        $input = $output = $cacheRead = 0;
        $stopReason = null;

        foreach ($this->sseFrames($body) as $data) {
            switch ($data['type'] ?? null) {
                case 'content_block_delta':
                    $delta = $data['delta']['text'] ?? '';
                    if ($delta !== '') {
                        $text .= $delta;
                        $onText($delta);
                    }
                    break;

                case 'message_start':
                    $usage = $data['message']['usage'] ?? [];
                    $input = (int) ($usage['input_tokens'] ?? 0);
                    $cacheRead = (int) ($usage['cache_read_input_tokens'] ?? 0);
                    break;

                case 'message_delta':
                    $output = (int) ($data['usage']['output_tokens'] ?? $output);
                    $stopReason = $data['delta']['stop_reason'] ?? $stopReason;
                    break;
            }
        }

        return new LlmStreamResult($text, $model, $input, $output, $cacheRead, $stopReason);
    }
}
