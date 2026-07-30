<?php

namespace App\Services\Chat;

use App\DTOs\LlmStreamResult;
use App\DTOs\TurnPlan;
use App\Events\LayerCompleted;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * The brain of the descent: it owns depth and checkpoint state, but knows nothing
 * about HTTP or streaming. It builds the plan for each assistant turn, then applies
 * the turn's result — advancing depth, gating progress, and firing rewards.
 */
class DescentService
{
    public function __construct(private readonly DescentPrompt $prompt) {}

    /**
     * Open a new rabbit hole. The first user message is the subject itself.
     */
    public function start(?User $user, string $subject): Conversation
    {
        $conversation = Conversation::create([
            'user_id' => $user?->id,
            'subject' => $subject,
            'current_depth' => 0,
            'status' => Conversation::STATUS_EXPLORING,
        ]);

        $this->appendMessage($conversation, Message::ROLE_USER, $subject);

        return $conversation;
    }

    /**
     * Record the learner's input. During a checkpoint it's their proof; while
     * exploring there is nothing to store (they're just asking to go deeper).
     */
    public function recordUserProof(Conversation $conversation, ?string $message): void
    {
        if ($conversation->isCheckpointPending() && filled($message)) {
            $this->appendMessage($conversation, Message::ROLE_USER, $message);
        }
    }

    /**
     * Build the plan for the next assistant turn from the conversation's state.
     * Pure — no side effects.
     */
    public function buildTurnPlan(Conversation $conversation): TurnPlan
    {
        $phase = $conversation->isCheckpointPending() ? Message::PHASE_GRADE : Message::PHASE_TEACH;
        $depth = $conversation->current_depth;

        $messages = $this->history($conversation);
        $messages[] = [
            'role' => Message::ROLE_USER,
            'content' => $phase === Message::PHASE_GRADE
                ? $this->prompt->gradeInstruction($depth)
                : $this->prompt->teachInstruction($depth),
        ];

        return new TurnPlan(
            conversation: $conversation,
            model: $this->pickModel($depth, $phase),
            phase: $phase,
            depth: $depth,
            system: $this->prompt->system(),
            messages: $messages,
            maxTokens: (int) config('platform.chat.max_tokens'),
        );
    }

    /**
     * Persist the assistant turn, advance the state machine, and (on a passed
     * layer) fire the reward event. Returns the payload for the SSE "done" event.
     */
    public function applyAssistantTurn(Conversation $conversation, TurnPlan $plan, LlmStreamResult $result): array
    {
        $this->appendMessage(
            $conversation,
            Message::ROLE_ASSISTANT,
            $result->text,
            $plan->phase,
            $result->model,
            $result->inputTokens,
            $result->outputTokens,
            $result->cacheReadTokens,
        );

        if ($plan->phase === Message::PHASE_GRADE) {
            return $this->resolveGrade($conversation, $this->parseControl($result->text));
        }

        // A teaching turn always ends on a checkpoint awaiting the learner's proof.
        $conversation->update(['status' => Conversation::STATUS_CHECKPOINT_PENDING]);

        return $this->payload($conversation);
    }

    /**
     * Route to a model by depth + phase: deep layers earn the premium model;
     * shallow teaching uses the mid model; grading uses the cheap one.
     */
    public function pickModel(int $depth, string $phase): string
    {
        $models = config('platform.chat.models.'.config('platform.chat.provider'));

        if ($depth >= (int) config('platform.chat.deep_threshold')) {
            return $models['deep'];
        }

        return $phase === Message::PHASE_GRADE ? $models['cheap'] : $models['mid'];
    }

    public function dailyLimitReached(?User $user, string $guestKey): bool
    {
        [$cacheKey, $limit] = $this->limitFor($user, $guestKey);

        return (int) Cache::get($cacheKey, 0) >= $limit;
    }

    /**
     * Count one assistant turn against today's quota (auto-expires at midnight).
     */
    public function recordTurn(?User $user, string $guestKey): void
    {
        [$cacheKey] = $this->limitFor($user, $guestKey);

        Cache::add($cacheKey, 0, now()->endOfDay());
        Cache::increment($cacheKey);
    }

    private function resolveGrade(Conversation $conversation, array $control): array
    {
        if (($control['verdict'] ?? 'retry') !== 'pass') {
            return $this->payload($conversation); // stays checkpoint_pending — try again
        }

        $passedDepth = $conversation->current_depth;
        $surfaced = ($passedDepth + 1) >= (int) config('platform.chat.max_depth');

        $conversation->update([
            'current_depth' => $surfaced ? $passedDepth : $passedDepth + 1,
            'status' => $surfaced ? Conversation::STATUS_SURFACED : Conversation::STATUS_EXPLORING,
        ]);

        // Guests earn nothing until they claim the hole — a natural sign-up hook.
        if ($conversation->user_id) {
            event(new LayerCompleted($conversation->user, $conversation, $passedDepth));
        }

        return $this->payload($conversation->refresh(), passed: true);
    }

    /**
     * The model's turn ends with a fenced ```json control block. Parse the last
     * one; a missing or malformed block falls back to the safe state (a grade
     * defaults to retry, a teach stays exploring).
     */
    private function parseControl(string $text): array
    {
        if (preg_match_all('/```json\s*(\{.*?\})\s*```/s', $text, $matches) && $matches[1]) {
            $decoded = json_decode(end($matches[1]), true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }

    /**
     * The last N turns mapped to the API's message shape, oldest first.
     *
     * @return array<int, array{role: string, content: string}>
     */
    private function history(Conversation $conversation): array
    {
        return $conversation->messages()
            ->latest('id')
            ->take((int) config('platform.chat.history_limit'))
            ->get()
            ->sortBy('id')
            ->map(fn (Message $message) => ['role' => $message->role, 'content' => $message->content])
            ->values()
            ->all();
    }

    private function appendMessage(
        Conversation $conversation,
        string $role,
        string $content,
        ?string $phase = null,
        ?string $model = null,
        ?int $inputTokens = null,
        ?int $outputTokens = null,
        ?int $cacheReadTokens = null,
    ): Message {
        $message = $conversation->messages()->create([
            'role' => $role,
            'content' => $content,
            'depth' => $conversation->current_depth,
            'phase' => $phase,
            'model' => $model,
            'input_tokens' => $inputTokens,
            'output_tokens' => $outputTokens,
            'cache_read_tokens' => $cacheReadTokens,
        ]);

        $conversation->increment('message_count');

        return $message;
    }

    private function limitFor(?User $user, string $guestKey): array
    {
        $today = now()->toDateString();

        if ($user) {
            return ["dth:turns:user:{$user->id}:{$today}", (int) config('platform.chat.user_daily_limit')];
        }

        return ["dth:turns:guest:{$guestKey}:{$today}", (int) config('platform.chat.guest_daily_limit')];
    }

    private function payload(Conversation $conversation, bool $passed = false): array
    {
        return [
            'status' => $conversation->status,
            'depth' => $conversation->current_depth,
            'max_depth' => (int) config('platform.chat.max_depth'),
            'passed' => $passed,
            'surfaced' => $conversation->isSurfaced(),
        ];
    }
}
