<?php

namespace App\Services\Chat;

use App\Contracts\LlmClient;
use App\DTOs\Llm\GradingRequest;
use App\DTOs\Llm\GradingResult;
use App\DTOs\Llm\LlmStream;
use App\DTOs\Llm\LlmUsage;
use App\DTOs\Llm\TeachingRequest;
use App\Events\LayerCompleted;
use App\Jobs\CompactConversationContext;
use App\Models\CheckpointAttempt;
use App\Models\Conversation;
use App\Models\LearningMode;
use App\Models\Message;
use App\Models\User;
use App\Services\ValidationService;
use App\Traits\ValidationHelper;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Throwable;

/**
 * The brain of the descent: it owns depth and checkpoint state, but knows nothing
 * about HTTP or streaming. It builds the request for each teaching turn, grades
 * the learner's proof, and applies the outcome — advancing depth, updating the
 * mastery map, and firing rewards.
 *
 * The teaching turn streams prose; the grading turn is a separate, non-streaming
 * structured-output call. That split is deliberate: verdicts are far too
 * load-bearing to be scraped out of the tail of a Markdown response.
 */
class DescentService
{
    use ValidationHelper;

    public const string REFRAME_DIFFERENT = 'different';

    public const string REFRAME_ANALOGY = 'analogy';

    public const string REFRAME_CHALLENGE = 'challenge';

    public const string REFRAME_EVIDENCE = 'evidence';

    public const array REFRAMES = [
        self::REFRAME_DIFFERENT,
        self::REFRAME_ANALOGY,
        self::REFRAME_CHALLENGE,
        self::REFRAME_EVIDENCE,
    ];

    public function __construct(
        private readonly DescentPrompt  $prompt,
        private readonly MasteryService $mastery,
        private readonly SourceFetcher  $sources,
        private readonly LlmClient      $llm,
    )
    {
        $this->initializeValidator();
    }

    /**
     * Open a subject from whatever the learner typed.
     *
     * A URL in the prompt means "teach me *this page*": it is fetched, stored as
     * the subject's source, and the guide is grounded in it from the first layer.
     * Anything the learner typed alongside the link wins as the subject name —
     * it is their framing of why they're reading it — otherwise the page's own
     * title stands in.
     *
     * @return ValidationService `conversation` on success.
     */
    public function open(?User $user, string $prompt, ?LearningMode $mode = null): ValidationService
    {
        $url = $this->firstUrl($prompt);

        if ($url === null) {
            return $this->addValidatedItems(['conversation' => $this->start($user, $prompt, $mode)]);
        }

        $fetch = $this->sources->fetch($url);

        if (!$fetch->isSuccessfulCheck()) {
            return $fetch;
        }

        $attributes = $fetch->getValidatedItem('attributes');
        $aside = trim(str_replace($url, '', $prompt), " \t\n\r—-–:·|");

        $conversation = $this->start(
            $user,
            $aside !== '' ? $aside : ($attributes['title'] ?: $attributes['site']),
            $mode,
        );

        $conversation->sources()->create($attributes);

        return $this->addValidatedItems(['conversation' => $conversation]);
    }

    /**
     * Open a new subject. The first user message is the subject itself.
     *
     * The subject is pinned to the language the learner is reading the app in,
     * and keeps it for the whole descent. The guide is told to follow the
     * learner over this if they write in something else — the stored locale is
     * the opening bid, not a lock.
     */
    public function start(?User $user, string $subject, ?LearningMode $mode = null): Conversation
    {
        $subject = Str::of($subject)->trim()->limit(500, '')->value();
        $conversation = Conversation::create([
            'user_id' => $user?->id,
            'learning_mode_id' => ($mode ?? LearningMode::default())?->id,
            'subject' => $subject,
            'locale' => app()->getLocale(),
            'title' => Str::limit($subject, 60),
            'current_depth' => 0,
            'status' => Conversation::STATUS_EXPLORING,
        ]);

        $this->appendMessage($conversation, Message::ROLE_USER, $subject);

        return $conversation;
    }

    /**
     * Build the request for the next teaching turn. Pure — no side effects.
     */
    public function teachingRequest(Conversation $conversation, ?string $reframe = null): TeachingRequest
    {
        $messages = $this->history($conversation);
        $messages[] = [
            'role' => Message::ROLE_USER,
            'content' => $reframe
                ? $this->prompt->reframeInstruction($conversation, $reframe)
                : $this->prompt->teachInstruction($conversation, $this->mastery->resurfacing($conversation)),
        ];

        return new TeachingRequest(
            model: $this->pickModel($conversation->current_depth, Message::PHASE_TEACH),
            system: $this->prompt->system(),
            messages: $messages,
            depth: $conversation->current_depth,
            maxTokens: (int)config('platform.chat.max_tokens'),
        );
    }

    /**
     * Persist a streamed teaching turn and open its checkpoint. Called after the
     * stream closes — including when the learner disconnected mid-turn, in which
     * case the partial text is still worth keeping.
     *
     * @return array<string, mixed>
     */
    public function applyTeachingTurn(Conversation $conversation, LlmStream $stream): array
    {
        $text = trim($stream->text());

        if ($text === '') {
            return $this->state($conversation);
        }

        $this->appendMessage(
            conversation: $conversation,
            role: Message::ROLE_ASSISTANT,
            content: $text,
            phase: Message::PHASE_TEACH,
            model: $stream->model,
            usage: $stream->usage(),
        );

        // A teaching turn always ends on a checkpoint awaiting the learner's proof.
        $conversation->update(['status' => Conversation::STATUS_CHECKPOINT_PENDING]);

        return $this->state($conversation->refresh());
    }

    /**
     * Grade the learner's proof, apply the outcome, and return both the verdict
     * and the new conversation state.
     *
     * @return array{attempt: CheckpointAttempt, result: GradingResult, state: array<string, mixed>}
     */
    public function gradeCheckpoint(Conversation $conversation, string $answer, ?int $selfRating = null): array
    {
        $depth = $conversation->current_depth;

        $answerMessage = $this->appendMessage($conversation, Message::ROLE_USER, trim($answer));

        $result = $this->grade($conversation, $answer, $selfRating, $depth);

        $this->appendMessage(
            conversation: $conversation,
            role: Message::ROLE_ASSISTANT,
            content: $result->feedback,
            phase: Message::PHASE_GRADE,
            model: $result->model,
            usage: $result->usage,
        );

        $attempt = $conversation->checkpointAttempts()->create($result->toAttributes() + [
                'message_id' => $answerMessage->id,
                'depth' => $depth,
                'self_rating' => $selfRating,
            ]);

        $this->mastery->record($conversation, $result, $depth);

        if ($result->passed()) {
            $this->clearLayer($conversation, $depth);
        }

        return [
            'attempt' => $attempt,
            'result' => $result,
            'state' => $this->state($conversation->refresh(), passed: $result->passed()),
        ];
    }

    /**
     * Route to a model by depth + phase: deep layers earn the premium model;
     * shallow teaching uses the mid model; grading uses the cheap one.
     */
    public function pickModel(int $depth, string $phase): string
    {
        $models = config('platform.chat.models.' . config('platform.chat.provider'));

        if ($phase === Message::PHASE_GRADE) {
            return $models['cheap'];
        }

        return $depth >= (int)config('platform.chat.deep_threshold') ? $models['deep'] : $models['mid'];
    }

    public function dailyLimitReached(?User $user, string $guestKey): bool
    {
        [$cacheKey, $limit] = $this->limitFor($user, $guestKey);

        return (int)Cache::get($cacheKey, 0) >= $limit;
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

    /**
     * The checkpoint the learner is answering — the tail of the last teaching
     * turn. Falls back to the whole turn, which is still a complete brief for
     * the grader, so this convenience can never break grading.
     */
    public function currentCheckpoint(Conversation $conversation): string
    {
        $teaching = $conversation->messages()
            ->where('role', Message::ROLE_ASSISTANT)
            ->where('phase', Message::PHASE_TEACH)
            ->latest('id')
            ->value('content');

        if (!$teaching) {
            return '';
        }

        return Str::of($teaching)->after('**Checkpoint:**')->trim()->value() ?: trim($teaching);
    }

    /**
     * The conversation state the browser needs after any turn.
     *
     * @return array<string, mixed>
     */
    public function state(Conversation $conversation, bool $passed = false): array
    {
        return [
            'status' => $conversation->status,
            'depth' => $conversation->current_depth,
            'max_depth' => (int)config('platform.chat.max_depth'),
            'passed' => $passed,
            'surfaced' => $conversation->isSurfaced(),
            'mastery' => $this->mastery->tally($conversation),
        ];
    }

    /**
     * A failed grading call must never cost the learner their layer, so the
     * unavailable verdict is a retry with an honest message.
     */
    private function grade(Conversation $conversation, string $answer, ?int $selfRating, int $depth): GradingResult
    {
        try {
            return $this->llm->gradeCheckpoint(new GradingRequest(
                model: $this->pickModel($depth, Message::PHASE_GRADE),
                system: $this->prompt->system(),
                subject: $conversation->subject,
                checkpoint: $this->currentCheckpoint($conversation),
                answer: $answer,
                depth: $depth,
                maxTokens: (int)config('platform.chat.grade_max_tokens'),
                language: $conversation->language(),
                selfRating: $selfRating,
            ));
        } catch (Throwable $e) {
            fullLog($e);

            return GradingResult::unavailable(__('frontend.chat.grade-unavailable'));
        }
    }

    /**
     * A passed layer: descend (or surface), reward, and schedule compaction.
     */
    private function clearLayer(Conversation $conversation, int $passedDepth): void
    {
        $surfaced = ($passedDepth + 1) >= (int)config('platform.chat.max_depth');

        $conversation->update([
            'current_depth' => $surfaced ? $passedDepth : $passedDepth + 1,
            'status' => $surfaced ? Conversation::STATUS_SURFACED : Conversation::STATUS_EXPLORING,
            'surfaced_at' => $surfaced ? now() : null,
        ]);

        // Guests earn nothing until they claim the subject — a natural sign-up hook.
        if ($conversation->user_id) {
            event(new LayerCompleted($conversation->user, $conversation, $passedDepth));
        }

        if ($conversation->message_count > (int)config('platform.chat.summarize_after')) {
            CompactConversationContext::dispatch($conversation->id);
        }
    }

    /**
     * The messages sent to the provider: the running summary (if any) followed
     * by the most recent turns, oldest first. Grader feedback is included —
     * it is what the learner just read.
     *
     * @return array<int, array{role: string, content: string}>
     */
    private function history(Conversation $conversation): array
    {
        $messages = $conversation->messages()
            ->latest('id')
            ->take((int)config('platform.chat.history_limit'))
            ->get()
            ->sortBy('id')
            ->map(fn(Message $message) => ['role' => $message->role, 'content' => $message->content])
            ->values()
            ->all();

        if (!$conversation->summary) {
            return $messages;
        }

        array_unshift($messages, [
            'role' => Message::ROLE_USER,
            'content' => $this->prompt->summaryPreamble($conversation->summary),
        ]);

        return $messages;
    }

    private function appendMessage(
        Conversation $conversation,
        string       $role,
        string       $content,
        ?string      $phase = null,
        ?string      $model = null,
        ?LlmUsage    $usage = null,
    ): Message
    {
        $message = $conversation->messages()->create([
            'role' => $role,
            'content' => $content,
            'depth' => $conversation->current_depth,
            'phase' => $phase,
            'model' => $model,
            'input_tokens' => $usage?->inputTokens,
            'output_tokens' => $usage?->outputTokens,
            'cache_read_tokens' => $usage?->cacheReadTokens,
        ]);

        $conversation->increment('message_count');

        return $message;
    }

    /**
     * The first http(s) URL in the prompt, minus any sentence punctuation that
     * ran into it. Returns null when the learner just named a subject.
     */
    private function firstUrl(string $prompt): ?string
    {
        if (!preg_match('~https?://[^\s<>"\'\)\]]+~i', $prompt, $match)) {
            return null;
        }

        return rtrim($match[0], '.,;:!?') ?: null;
    }

    /**
     * @return array{0: string, 1: int}
     */
    private function limitFor(?User $user, string $guestKey): array
    {
        $today = now()->toDateString();

        if ($user) {
            return ["dth:turns:user:{$user->id}:{$today}", (int)config('platform.chat.user_daily_limit')];
        }

        return ["dth:turns:guest:{$guestKey}:{$today}", (int)config('platform.chat.guest_daily_limit')];
    }
}
