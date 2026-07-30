<?php

namespace App\Services\Chat;

use App\Contracts\LlmClient;
use App\Models\Conversation;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Owns the SSE wire for a teaching turn: it streams the guide's prose to the
 * browser, then hands the assembled turn to DescentService to persist and open
 * the checkpoint. Knows the transport; the descent logic lives next door.
 *
 * Events sent: `token` (a text delta), `done` (final state payload), `error`.
 */
class ChatStreamingService
{
    public function __construct(
        private readonly LlmClient $llm,
        private readonly DescentService $descent,
    ) {}

    public function stream(Conversation $conversation, ?string $reframe = null): StreamedResponse
    {
        $request = $this->descent->teachingRequest($conversation, $reframe);

        return response()->stream(function () use ($conversation, $request) {
            try {
                $stream = $this->llm->streamTeachingTurn($request);

                foreach ($stream as $delta) {
                    $this->send('token', ['text' => $delta]);
                }
            } catch (Throwable $e) {
                fullLog($e);
                $this->send('error', ['message' => __('frontend.chat.llm-error')]);

                return;
            }

            // Persist + advance even if the learner disconnected mid-stream: the
            // stream stops early and hands back everything received so far.
            $this->send('done', $this->descent->applyTeachingTurn($conversation, $stream));
        }, 200, $this->headers());
    }

    /**
     * A one-frame error stream, so the browser's reader sees a normal `error`
     * event instead of a dropped connection it has to guess about.
     */
    public function error(string $message): StreamedResponse
    {
        return response()->stream(function () use ($message) {
            $this->send('error', ['message' => $message]);
        }, 200, $this->headers());
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function send(string $event, array $data): void
    {
        echo 'event: '.$event."\n";
        echo 'data: '.json_encode($data)."\n\n";

        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
    }

    /**
     * @return array<string, string>
     */
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
