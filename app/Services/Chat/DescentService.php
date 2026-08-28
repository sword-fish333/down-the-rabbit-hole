<?php

namespace App\Services\Chat;

use App\Contracts\LlmClient;
use App\DTOs\Llm\GradingRequest;
use App\DTOs\Llm\GradingResult;
use App\DTOs\Llm\LlmStream;
use App\DTOs\Llm\LlmUsage;
use App\DTOs\Llm\TeachingRequest;
use App\Events\ConceptsMastered;
use App\Events\LayerCompleted;
use App\Jobs\CompactConversationContext;
use App\Models\CheckpointAttempt;
use App\Models\Concept;
use App\Models\Conversation;
use App\Models\LearningMode;
use App\Models\Message;
use App\Models\User;
use App\Services\ValidationService;
use App\Traits\ValidationHelper;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
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
        private readonly DescentPrompt $prompt,
        private readonly MasteryService $mastery,
        private readonly SourceFetcher $sources,
        private readonly LlmClient $llm,
    ) {
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
    public function open(?User $user, string $prompt, ?LearningMode $mode = null, ?string $approach = null): ValidationService
    {
        $this->remember($user, $approach);

        $url = $this->firstUrl($prompt);

        if ($url === null) {
            return $this->addValidatedItems(['conversation' => $this->start($user, $prompt, $mode, $approach)]);
        }

        $fetch = $this->sources->fetch($url);

        if (! $fetch->isSuccessfulCheck()) {
            return $fetch;
        }

        $attributes = $fetch->getValidatedItem('attributes');
        $aside = trim(str_replace($url, '', $prompt), " \t\n\r—-–:·|");

        $conversation = $this->start(
            $user,
            $aside !== '' ? $aside : ($attributes['title'] ?: $attributes['site']),
            $mode,
            $approach,
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
     *
     * The approach is pinned the same way, and for the same reason: switching it
     * later changes how the *next* layer opens, never how the ones already
     * behind them did.
     */
    public function start(?User $user, string $subject, ?LearningMode $mode = null, ?string $approach = null): Conversation
    {
        $subject = Str::of($subject)->trim()->limit(500, '')->value();
        $conversation = Conversation::create([
            'user_id' => $user?->id,
            'learning_mode_id' => ($mode ?? LearningMode::default())?->id,
            'approach' => $this->resolveApproach($user, $approach),
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
     * Which kind of turn opens the layer the learner is standing on.
     *
     * Derived from state rather than passed in, so there is exactly one rule and
     * no way for the browser to ask for a turn the descent isn't due. A reframe
     * always teaches; a question-first subject asks only for a layer nothing has
     * opened yet, which is precisely what makes "teach me this one" a plain
     * re-stream rather than a second endpoint.
     */
    public function turnPhase(Conversation $conversation, ?string $reframe = null): string
    {
        if ($reframe !== null) {
            return Message::PHASE_TEACH;
        }

        // Before either of the two ways a layer opens, there is the ground it
        // opens into — but only for a subject that has a page to survey.
        if ($this->awaitsSurvey($conversation)) {
            return Message::PHASE_SURVEY;
        }

        return $conversation->opensWithQuestion() && ! $this->layerOpened($conversation)
            ? Message::PHASE_QUESTION
            : Message::PHASE_TEACH;
    }

    /**
     * True when the next turn owed to this subject is the survey — SQ3R's first
     * step, and the one thing this product asked its learners to do without.
     *
     * Four conditions, each of them load-bearing. A page to survey, because you
     * cannot map ground that was never fetched. Depth 0, because a survey after
     * layer 03 is a spoiler. No survey already written, because it happens once.
     * And no layer opened yet, which is what keeps every source-backed subject
     * that was already underway when this shipped from being handed a map of
     * ground it has already walked.
     */
    public function awaitsSurvey(Conversation $conversation): bool
    {
        return $conversation->current_depth === 0
            && $conversation->sources()->exists()
            && ! $this->layerOpened($conversation)
            && ! $conversation->messages()->where('phase', Message::PHASE_SURVEY)->exists();
    }

    /**
     * True while a checkpoint is open on a layer that has never been taught —
     * the learner answered it cold, or is about to, and can still ask for the
     * lesson. False the instant the layer has been taught, which is what stops
     * the offer being made twice.
     */
    public function awaitsTeaching(Conversation $conversation): bool
    {
        return $conversation->isCheckpointPending() && ! $this->layerTaught($conversation);
    }

    /**
     * Build the request for the next teaching turn. Pure — no side effects.
     */
    public function teachingRequest(Conversation $conversation, ?string $reframe = null, string $phase = Message::PHASE_TEACH): TeachingRequest
    {
        $resurfacing = $this->mastery->resurfacing($conversation);

        $messages = $this->history($conversation);
        $messages[] = [
            'role' => Message::ROLE_USER,
            'content' => match (true) {
                $reframe !== null => $this->prompt->reframeInstruction($conversation, $reframe, $resurfacing),
                $phase === Message::PHASE_SURVEY => $this->prompt->surveyInstruction($conversation, $resurfacing),
                $phase === Message::PHASE_QUESTION => $this->prompt->questionInstruction($conversation, $resurfacing),
                default => $this->prompt->teachInstruction($conversation, $resurfacing),
            },
        ];

        return new TeachingRequest(
            model: $this->pickModel($conversation->current_depth, $phase),
            system: $this->prompt->system(),
            messages: $messages,
            depth: $conversation->current_depth,
            maxTokens: (int) config('platform.chat.max_tokens'),
        );
    }

    /**
     * Persist a streamed opening turn and open its checkpoint. Called after the
     * stream closes — including when the learner disconnected mid-turn, in which
     * case the partial text is still worth keeping.
     *
     * @return array<string, mixed>
     */
    public function applyTeachingTurn(Conversation $conversation, LlmStream $stream, string $phase = Message::PHASE_TEACH): array
    {
        $text = trim($stream->text());

        if ($text === '') {
            return $this->state($conversation);
        }

        $this->appendMessage(
            conversation: $conversation,
            role: Message::ROLE_ASSISTANT,
            content: $text,
            phase: $phase,
            model: $stream->model,
            usage: $stream->usage(),
        );

        // Taught or merely posed, the turn ends on a checkpoint awaiting the
        // learner's proof — a survey does not. It maps the ground and hands the
        // subject straight back to "open the first layer".
        $conversation->update(['status' => $phase === Message::PHASE_SURVEY
            ? Conversation::STATUS_EXPLORING
            : Conversation::STATUS_CHECKPOINT_PENDING]);

        // The phase rides along so the browser can label the turn it just
        // watched arrive. It belongs to this turn, not to the subject, which is
        // why it is not part of state().
        return $this->state($conversation->refresh()) + ['phase' => $phase];
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

        // Counted before this attempt exists: "first try" means no earlier
        // attempt at this layer, whether it was taught first or asked cold.
        $earlierAttempts = $conversation->checkpointAttempts()->where('depth', $depth)->count();

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

        $this->rewardMastery($conversation, $this->mastery->record($conversation, $result, $depth));

        if ($result->passed()) {
            $this->clearLayer($conversation, $depth, firstTry: $earlierAttempts === 0);
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
        $models = config('platform.chat.models.'.config('platform.chat.provider'));

        if ($phase === Message::PHASE_GRADE) {
            return $models['cheap'];
        }

        return $depth >= (int) config('platform.chat.deep_threshold') ? $models['deep'] : $models['mid'];
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

    /**
     * The checkpoint the learner is answering — the tail of the last teaching
     * turn. Falls back to the whole turn, which is still a complete brief for
     * the grader, so this convenience can never break grading.
     */
    public function currentCheckpoint(Conversation $conversation): string
    {
        $opening = $this->openingTurns($conversation)->latest('id')->value('content');

        if (! $opening) {
            return '';
        }

        return Str::of($opening)->after('**Checkpoint:**')->trim()->value() ?: trim($opening);
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
            'max_depth' => (int) config('platform.chat.max_depth'),
            'passed' => $passed,
            'surfaced' => $conversation->isSurfaced(),
            'awaits_teaching' => $this->awaitsTeaching($conversation),
            'awaits_survey' => $this->awaitsSurvey($conversation),
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
                maxTokens: (int) config('platform.chat.grade_max_tokens'),
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
    private function clearLayer(Conversation $conversation, int $passedDepth, bool $firstTry = false): void
    {
        $surfaced = ($passedDepth + 1) >= (int) config('platform.chat.max_depth');

        $conversation->update([
            'current_depth' => $surfaced ? $passedDepth : $passedDepth + 1,
            'status' => $surfaced ? Conversation::STATUS_SURFACED : Conversation::STATUS_EXPLORING,
            'surfaced_at' => $surfaced ? now() : null,
        ]);

        // Guests earn nothing until they claim the subject — a natural sign-up hook.
        if ($conversation->user_id) {
            event(new LayerCompleted($conversation->user, $conversation, $passedDepth, $firstTry, $surfaced));
        }

        if ($conversation->message_count > (int) config('platform.chat.summarize_after')) {
            CompactConversationContext::dispatch($conversation->id);
        }
    }

    /**
     * @param  Collection<int, Concept>  $concepts
     */
    private function rewardMastery(Conversation $conversation, Collection $concepts): void
    {
        if ($concepts->isEmpty() || ! $conversation->user_id) {
            return;
        }

        event(new ConceptsMastered($conversation->user, $conversation, $concepts));
    }

    /**
     * The turns that open a layer — taught or merely posed. Both end on a
     * `**Checkpoint:**`, which is why the checkpoint reader looks at either.
     */
    private function openingTurns(Conversation $conversation): HasMany
    {
        return $conversation->messages()
            ->where('role', Message::ROLE_ASSISTANT)
            ->whereIn('phase', Message::OPENING_PHASES);
    }

    private function layerOpened(Conversation $conversation): bool
    {
        return $this->openingTurns($conversation)->where('depth', $conversation->current_depth)->exists();
    }

    private function layerTaught(Conversation $conversation): bool
    {
        return $conversation->messages()
            ->where('role', Message::ROLE_ASSISTANT)
            ->where('phase', Message::PHASE_TEACH)
            ->where('depth', $conversation->current_depth)
            ->exists();
    }

    /**
     * How a new subject opens: what the learner just picked, else what they
     * picked last time, else the product default.
     */
    private function resolveApproach(?User $user, ?string $approach): string
    {
        if (in_array($approach, Conversation::APPROACHES, true)) {
            return $approach;
        }

        return $user?->preferredApproach() ?? Conversation::APPROACH_GUIDED;
    }

    /**
     * A deliberate choice at the composer becomes the default for the next
     * subject — nobody should have to say "ask me first" every single time.
     */
    private function remember(?User $user, ?string $approach): void
    {
        if (! $user || ! in_array($approach, Conversation::APPROACHES, true) || $user->preferred_approach === $approach) {
            return;
        }

        $user->update(['preferred_approach' => $approach]);
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
            ->take((int) config('platform.chat.history_limit'))
            ->get()
            ->sortBy('id')
            ->map(fn (Message $message) => ['role' => $message->role, 'content' => $message->content])
            ->values()
            ->all();

        if (! $conversation->summary) {
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
        string $role,
        string $content,
        ?string $phase = null,
        ?string $model = null,
        ?LlmUsage $usage = null,
    ): Message {
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
        if (! preg_match('~https?://[^\s<>"\'\)\]]+~i', $prompt, $match)) {
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
            return ["dth:turns:user:{$user->id}:{$today}", (int) config('platform.chat.user_daily_limit')];
        }

        return ["dth:turns:guest:{$guestKey}:{$today}", (int) config('platform.chat.guest_daily_limit')];
    }
}
