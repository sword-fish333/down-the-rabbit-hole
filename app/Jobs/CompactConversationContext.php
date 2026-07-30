<?php

namespace App\Jobs;

use App\Contracts\LlmClient;
use App\DTOs\Llm\ContextSummaryRequest;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Folds the turns that fell out of the verbatim window into a running summary,
 * so a deep hole keeps its continuity without resending the whole transcript.
 *
 * Queued on purpose: this is a second LLM round-trip and must never sit on the
 * critical path of a learner waiting to descend. If it fails, nothing breaks —
 * the next turn simply carries the older summary (or none).
 */
class CompactConversationContext implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public function __construct(private readonly int $conversationId) {}

    public function handle(LlmClient $llm): void
    {
        $conversation = Conversation::find($this->conversationId);

        if (! $conversation) {
            return;
        }

        $keep = (int) config('platform.chat.history_limit');
        $stale = $conversation->messages()
            ->orderBy('id')
            ->take(max(0, $conversation->messages()->count() - $keep))
            ->get();

        if ($stale->isEmpty()) {
            return;
        }

        try {
            $summary = $llm->summarizeContext(new ContextSummaryRequest(
                model: config('platform.chat.models.'.config('platform.chat.provider').'.cheap'),
                subject: $conversation->subject,
                messages: $stale->map(fn (Message $message) => [
                    'role' => $message->role,
                    'content' => $message->content,
                ])->all(),
                depth: $conversation->current_depth,
                maxTokens: (int) config('platform.chat.summary_max_tokens'),
                previousSummary: $conversation->summary,
            ));
        } catch (Throwable $e) {
            fullLog($e);

            return;
        }

        if (! $summary->isEmpty()) {
            $conversation->update(['summary' => $summary->summary]);
        }
    }
}
