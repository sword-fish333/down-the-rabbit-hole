<?php

namespace App\Services\Chat;

use App\Contracts\LlmClient;
use App\Models\Conversation;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Owns the SSE wire: it streams one assistant turn to the browser as Server-Sent
 * Events, then hands the assembled result to DescentService to persist and advance
 * state. Knows the transport; the descent logic lives in DescentService.
 *
 * Events sent: `token` (a text delta), `done` (final state payload), `error`.
 */
class ChatStreamingService
{
    public function __construct(
        private readonly LlmClient $llm,
        private readonly DescentService $descent,
    ) {}

    public function stream(Conversation $conversation): StreamedResponse
    {
        $plan = $this->descent->buildTurnPlan($conversation);

        return response()->stream(function () use ($conversation, $plan) {
            try {
                $result = $this->llm->streamTurn($plan, function (string $delta) {
                    $this->send('token', ['text' => $delta]);
                });
            } catch (Throwable $e) {
                fullLog($e);
                $this->send('error', ['message' => __('frontend.chat.llm-error')]);

                return;
            }

            // Persist + advance even if the learner disconnected mid-stream
            // (streamTurn returns the partial result rather than throwing).
            $payload = $this->descent->applyAssistantTurn($conversation, $plan, $result);
            $this->send('done', $payload);
        }, 200, $this->headers());
    }

    private function send(string $event, array $data): void
    {
        echo 'event: '.$event."\n";
        echo 'data: '.json_encode($data)."\n\n";

        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
    }

    private function headers(): array
    {
        return [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no', // stop nginx/proxies buffering the stream
        ];
    }
}
