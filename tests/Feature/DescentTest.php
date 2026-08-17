<?php

namespace Tests\Feature;

use App\Contracts\LlmClient;
use App\Models\CheckpointAttempt;
use App\Models\Concept;
use App\Models\Conversation;
use App\Models\LearningMode;
use App\Models\Message;
use App\Models\User;
use App\Models\XpEvent;
use App\Services\Chat\DescentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FakeLlmClient;
use Tests\TestCase;

/**
 * Drives the descent state machine offline — the provider is a FakeLlmClient,
 * so no API key or network is needed.
 */
class DescentTest extends TestCase
{
    use RefreshDatabase;

    private FakeLlmClient $llm;

    protected function setUp(): void
    {
        parent::setUp();

        $this->llm = new FakeLlmClient;
        $this->app->instance(LlmClient::class, $this->llm);
    }

    private function descent(): DescentService
    {
        return app(DescentService::class);
    }

    /** The per-turn directive — the last message in the request the guide saw. */
    private function lastDirective(): string
    {
        $messages = $this->llm->lastTeachingRequest->messages;

        return end($messages)['content'];
    }

    /**
     * Runs one full opening turn through the service, deriving the phase exactly
     * as ChatStreamingService does — so a test can never open a layer in a way
     * the app itself cannot.
     */
    private function teach(Conversation $conversation, ?string $reframe = null): void
    {
        $phase = $this->descent()->turnPhase($conversation, $reframe);
        $request = $this->descent()->teachingRequest($conversation, $reframe, $phase);
        $stream = $this->llm->streamTeachingTurn($request);

        foreach ($stream as $chunk) {
            // Draining the stream is what makes text() and usage() available.
        }

        $this->descent()->applyTeachingTurn($conversation, $stream, $phase);
        $conversation->refresh();
    }

    public function test_a_teaching_turn_opens_a_checkpoint(): void
    {
        $conversation = $this->descent()->start(null, 'Quantum entanglement');

        $this->assertSame(Conversation::STATUS_EXPLORING, $conversation->status);
        $this->assertSame(1, $conversation->messages()->count());

        $this->teach($conversation);

        $this->assertSame(Conversation::STATUS_CHECKPOINT_PENDING, $conversation->status);
        $this->assertStringContainsString('Checkpoint', $conversation->messages()->latest('id')->value('content'));
    }

    public function test_the_teaching_turn_carries_no_control_block(): void
    {
        $conversation = $this->descent()->start(null, 'Pure functions');
        $this->teach($conversation);

        // The trailing ```json control block is gone: grading is a separate,
        // schema-constrained call, so there is nothing to scrape out of prose.
        $this->assertStringNotContainsString('```json', $conversation->messages()->latest('id')->value('content'));
    }

    public function test_passing_a_checkpoint_descends_a_layer(): void
    {
        $conversation = $this->descent()->start(null, 'Stoicism');
        $this->teach($conversation);

        $graded = $this->descent()->gradeCheckpoint($conversation, 'My explanation in my own words.');

        $this->assertTrue($graded['result']->passed());
        $this->assertSame(1, $conversation->fresh()->current_depth);
        $this->assertSame(Conversation::STATUS_EXPLORING, $conversation->fresh()->status);
        $this->assertTrue($graded['state']['passed']);
    }

    public function test_a_retry_keeps_the_learner_on_the_layer(): void
    {
        $this->llm->verdict = CheckpointAttempt::VERDICT_RETRY;

        $conversation = $this->descent()->start(null, 'The fall of Rome');
        $this->teach($conversation);

        $this->descent()->gradeCheckpoint($conversation, 'A weak answer.');

        $conversation->refresh();
        $this->assertSame(0, $conversation->current_depth);
        $this->assertSame(Conversation::STATUS_CHECKPOINT_PENDING, $conversation->status);
    }

    public function test_a_failed_grading_call_never_costs_the_learner_their_layer(): void
    {
        $this->llm->gradeThrows = true;

        $conversation = $this->descent()->start(null, 'Chess');
        $this->teach($conversation);

        $graded = $this->descent()->gradeCheckpoint($conversation, 'a good answer');

        $this->assertFalse($graded['result']->passed());
        $this->assertSame(0, $conversation->fresh()->current_depth);
        $this->assertSame(Conversation::STATUS_CHECKPOINT_PENDING, $conversation->fresh()->status);
    }

    public function test_a_graded_checkpoint_is_recorded_with_its_criteria(): void
    {
        $conversation = $this->descent()->start(null, 'Recursion');
        $this->teach($conversation);

        $this->descent()->gradeCheckpoint($conversation, 'Base case plus a smaller call.', selfRating: 90);

        $attempt = CheckpointAttempt::firstOrFail();
        $this->assertSame(CheckpointAttempt::VERDICT_PASS, $attempt->verdict);
        $this->assertSame(86, $attempt->score);
        $this->assertSame(90, $attempt->self_rating);
        $this->assertCount(1, $attempt->criteria);
        $this->assertSame(4, $attempt->calibrationGap());
    }

    public function test_the_grader_receives_the_checkpoint_and_the_answer(): void
    {
        $conversation = $this->descent()->start(null, 'Entropy');
        $this->teach($conversation);

        $this->descent()->gradeCheckpoint($conversation, 'Disorder increases.');

        $this->assertSame('Explain it back.', $this->llm->lastGradingRequest->checkpoint);
        $this->assertSame('Disorder increases.', $this->llm->lastGradingRequest->answer);
        $this->assertSame('Entropy', $this->llm->lastGradingRequest->subject);
    }

    public function test_demonstrated_concepts_build_the_mastery_map(): void
    {
        config(['platform.mastery.demonstrations_to_master' => 2]);

        $conversation = $this->descent()->start(null, 'Pure functions');

        $this->teach($conversation);
        $this->descent()->gradeCheckpoint($conversation, 'first proof');

        $concept = Concept::firstOrFail();
        $this->assertSame(Concept::STATE_DEVELOPING, $concept->state, 'One demonstration is not mastery.');

        $this->teach($conversation);
        $this->descent()->gradeCheckpoint($conversation, 'second proof');

        $this->assertSame(Concept::STATE_MASTERED, $concept->fresh()->state);
    }

    public function test_a_misconception_is_recorded_and_resurfaces_in_the_next_layer(): void
    {
        $this->llm->misconceptions = [[
            'concept' => 'Referential transparency',
            'belief' => 'that logging counts as pure',
            'correction' => 'Writing anywhere outside the function is a side effect.',
        ]];

        $conversation = $this->descent()->start(null, 'Pure functions');
        $this->teach($conversation);
        $this->descent()->gradeCheckpoint($conversation, 'an answer with a wrong belief');

        $concept = Concept::where('slug', 'referential-transparency')->firstOrFail();
        $this->assertSame(Concept::STATE_MISUNDERSTOOD, $concept->state);
        $this->assertSame('that logging counts as pure', $concept->note);

        // The next teaching turn is asked to weave a correction in.
        $this->teach($conversation);
        $directive = $this->lastDirective();
        $this->assertStringContainsString('Referential transparency', $directive);
    }

    public function test_the_learning_mode_directive_reaches_the_teaching_turn(): void
    {
        $mode = LearningMode::create([
            'slug' => 'socratic',
            'name' => 'Socratic',
            'prompt_directive' => 'Lead with a question that exposes the gap.',
        ]);

        $conversation = $this->descent()->start(null, 'Logic', $mode);
        $this->teach($conversation);

        $directive = $this->lastDirective();
        $this->assertStringContainsString('Lead with a question', $directive);
    }

    public function test_a_reframe_re_teaches_the_same_layer(): void
    {
        $conversation = $this->descent()->start(null, 'Monads');
        $this->teach($conversation);

        $this->teach($conversation, DescentService::REFRAME_ANALOGY);

        $directive = $this->lastDirective();
        $this->assertStringContainsString('analogy', $directive);
        $this->assertSame(0, $conversation->fresh()->current_depth, 'A reframe must not advance depth.');
    }

    public function test_surfacing_at_max_depth_closes_the_hole(): void
    {
        config(['platform.chat.max_depth' => 2]);

        $conversation = $this->descent()->start(null, 'Go');

        $this->teach($conversation);
        $this->descent()->gradeCheckpoint($conversation, 'layer 0 proof');
        $this->teach($conversation->fresh());
        $this->descent()->gradeCheckpoint($conversation->fresh(), 'layer 1 proof');

        $conversation->refresh();
        $this->assertSame(Conversation::STATUS_SURFACED, $conversation->status);
        $this->assertNotNull($conversation->surfaced_at);
    }

    public function test_model_routes_by_depth_and_phase(): void
    {
        config(['platform.chat.deep_threshold' => 3]);

        $models = config('platform.chat.models.'.config('platform.chat.provider'));
        $descent = $this->descent();

        $this->assertSame($models['mid'], $descent->pickModel(0, Message::PHASE_TEACH));
        $this->assertSame($models['cheap'], $descent->pickModel(0, Message::PHASE_GRADE));
        $this->assertSame($models['cheap'], $descent->pickModel(6, Message::PHASE_GRADE), 'Grading always uses the cheap model.');
        $this->assertSame($models['deep'], $descent->pickModel(3, Message::PHASE_TEACH));
    }

    public function test_an_authenticated_pass_awards_xp_and_streak(): void
    {
        $user = User::create(['first_name' => 'Alice', 'email' => 'alice@example.com', 'password' => 'secret-pass1']);

        $conversation = $this->descent()->start($user, 'Neuroscience');
        $this->teach($conversation);
        $this->descent()->gradeCheckpoint($conversation, 'A sound explanation.');

        // Layer 00 cleared without a failed attempt: the layer award plus the
        // first-try bonus, each its own ledger row.
        $expected = (int) config('platform.rewards.layer_xp') + (int) config('platform.rewards.first_try_xp');

        $this->assertSame($expected, $user->fresh()->xp);
        $this->assertSame(1, XpEvent::where('type', XpEvent::TYPE_LAYER_COMPLETED)->count());
        $this->assertSame(1, XpEvent::where('type', XpEvent::TYPE_FIRST_TRY)->count());
        $this->assertSame(1, (int) $user->streak()->first()->current_count);
    }

    /** Deeper layers are worth more, and a retried layer forfeits the bonus. */
    public function test_xp_scales_with_depth_and_rewards_a_clean_first_attempt(): void
    {
        config(['platform.chat.max_depth' => 9]);

        $user = User::create(['first_name' => 'Bo', 'email' => 'bo@example.com', 'password' => 'secret-pass1']);
        $conversation = $this->descent()->start($user, 'Topology');

        // Layer 00: missed once, then cleared — no first-try bonus.
        $this->teach($conversation);
        $this->llm->verdict = CheckpointAttempt::VERDICT_RETRY;
        $this->descent()->gradeCheckpoint($conversation->fresh(), 'Not quite.');
        $this->llm->verdict = CheckpointAttempt::VERDICT_PASS;
        $this->descent()->gradeCheckpoint($conversation->fresh(), 'Better.');

        $this->assertSame(0, XpEvent::where('type', XpEvent::TYPE_FIRST_TRY)->count());
        $this->assertSame((int) config('platform.rewards.layer_xp'), $user->fresh()->xp);

        // Layer 01: cleared cleanly, worth one depth step more — and the second
        // demonstration of the same concept carries it into mastery.
        $this->teach($conversation->fresh());
        $this->descent()->gradeCheckpoint($conversation->fresh(), 'A sound explanation.');

        $this->assertSame(1, XpEvent::where('type', XpEvent::TYPE_CONCEPT_MASTERED)->count());
        $this->assertSame(
            (int) config('platform.rewards.layer_xp') * 2
                + (int) config('platform.rewards.layer_depth_xp')
                + (int) config('platform.rewards.first_try_xp')
                + (int) config('platform.rewards.concept_xp'),
            $user->fresh()->xp,
        );
    }

    public function test_a_guest_pass_awards_nothing(): void
    {
        $conversation = $this->descent()->start(null, 'Go');
        $this->teach($conversation);
        $this->descent()->gradeCheckpoint($conversation, 'answer');

        $this->assertSame(0, XpEvent::count());
    }

    public function test_the_daily_turn_limit_is_enforced_per_guest(): void
    {
        config(['platform.chat.guest_daily_limit' => 2]);

        $descent = $this->descent();
        $this->assertFalse($descent->dailyLimitReached(null, 'guest-key'));

        $descent->recordTurn(null, 'guest-key');
        $descent->recordTurn(null, 'guest-key');

        $this->assertTrue($descent->dailyLimitReached(null, 'guest-key'));
        $this->assertFalse($descent->dailyLimitReached(null, 'another-guest'));
    }
}
