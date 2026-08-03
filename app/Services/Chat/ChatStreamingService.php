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
 * Events sent: `stage` (what the guide is doing, before prose exists), `token`
 * (a text delta), `done` (final state payload), `error`.
 *
 * The stages are REAL. Each is emitted at the moment that work actually happens,
 * and its label carries the numbers behind it ("Recalling 4 concepts you've
 * proven"), so a learner watching the panel is reading progress rather than a
 * spinner dressed up as one. Nothing here fires on a timer, and a subject with
 * no source never claims to be reading one.
 */
class ChatStreamingService
{
    public function __construct(
        private readonly LlmClient $llm,
        private readonly DescentService $descent,
        private readonly MasteryService $mastery,
    ) {}

    public function stream(Conversation $conversation, ?string $reframe = null): StreamedResponse
    {
        return response()->stream(function () use ($conversation, $reframe) {
            try {
                foreach ($this->preflight($conversation) as [$key, $label]) {
                    $this->send('stage', ['key' => $key, 'label' => $label]);
                }

                // Built inside the stream so the browser already has the first
                // stages on screen while the history query runs.
                $request = $this->descent->teachingRequest($conversation, $reframe);

                $this->send('stage', [
                    'key' => 'composing',
                    'label' => __('frontend.chat.stage.composing', [
                        'layer' => str_pad((string) $conversation->current_depth, 2, '0', STR_PAD_LEFT),
                    ]),
                ]);

                $stream = $this->llm->streamTeachingTurn($request);
                $writing = false;

                foreach ($stream as $delta) {
                    if (! $writing) {
                        $writing = true;
                        $this->send('stage', ['key' => 'writing', 'label' => __('frontend.chat.stage.writing')]);
                    }

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
     * What the guide is doing before the first token exists — and only what is
     * genuinely true of this subject: no source means no "reading" line, no
     * proven concepts means no "recalling" line.
     *
     * @return array<int, array{0: string, 1: string}>
     */
    private function preflight(Conversation $conversation): array
    {
        $stages = [[
            'orienting',
            __('frontend.chat.stage.orienting', ['subject' => str($conversation->subject)->limit(60)->value()]),
        ]];

        if ($source = $conversation->sources->first()) {
            $stages[] = ['reading', __('frontend.chat.stage.reading', [
                'title' => str($source->displayTitle())->limit(60)->value(),
                'words' => number_format($source->words),
            ])];
        }

        $tally = $this->mastery->tally($conversation);

        if ($tally['mastered'] > 0) {
            $stages[] = ['recalling', trans_choice('frontend.chat.stage.recalling', $tally['mastered'], [
                'count' => $tally['mastered'],
            ])];
        }

        if ($tally['misunderstood'] > 0) {
            $stages[] = ['revisiting', trans_choice('frontend.chat.stage.revisiting', $tally['misunderstood'], [
                'count' => $tally['misunderstood'],
            ])];
        }

        return $stages;
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
