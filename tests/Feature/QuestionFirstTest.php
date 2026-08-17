<?php

namespace Tests\Feature;

use App\Contracts\LlmClient;
use App\Models\CheckpointAttempt;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\Chat\DescentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FakeLlmClient;
use Tests\TestCase;

/**
 * The second way through a layer: asked before taught.
 *
 * The whole feature rests on one derivation — which kind of turn a layer is due
 * — and that derivation is *state*, never a parameter. If the browser could ask
 * for a turn type, a learner could skip the cold question by asking to be taught
 * first, and the choice would mean nothing. So these tests pin the derivation
 * rather than the buttons that trigger it.
 */
class QuestionFirstTest extends TestCase
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

    /** One opening turn, with the phase derived exactly as the stream derives it. */
    private function open(Conversation $conversation, ?string $reframe = null): string
    {
        $phase = $this->descent()->turnPhase($conversation, $reframe);
        $stream = $this->llm->streamTeachingTurn($this->descent()->teachingRequest($conversation, $reframe, $phase));

        foreach ($stream as $chunk) {
        }

        $this->descent()->applyTeachingTurn($conversation, $stream, $phase);
        $conversation->refresh();

        return $phase;
    }

    public function test_a_guided_subject_always_opens_a_layer_by_teaching_it(): void
    {
        $subject = $this->descent()->start(null, 'Pure functions');

        $this->assertSame(Conversation::APPROACH_GUIDED, $subject->approach);
        $this->assertSame(Message::PHASE_TEACH, $this->open($subject));
        $this->assertFalse($this->descent()->awaitsTeaching($subject));
    }

    public function test_a_question_first_subject_poses_the_checkpoint_before_teaching(): void
    {
        $subject = $this->descent()->start(null, 'Pure functions', approach: Conversation::APPROACH_QUESTION);

        $this->assertSame(Message::PHASE_QUESTION, $this->open($subject));
        $this->assertSame(Conversation::STATUS_CHECKPOINT_PENDING, $subject->status);

        // The lesson is still owed, and the workspace is told so.
        $this->assertTrue($this->descent()->awaitsTeaching($subject));
        $this->assertTrue($this->descent()->state($subject)['awaits_teaching']);

        $messages = $this->llm->lastTeachingRequest->messages;
        $directive = end($messages)['content'];
        $this->assertStringContainsString('Do NOT teach layer 0', $directive);
        $this->assertStringContainsString('**Checkpoint:**', $directive);
    }

    /**
     * The escape hatch. It is the *same* stream with no argument — the server
     * sees a layer already posed and teaches it — so there is one endpoint and
     * one rule, and the depth is untouched either way.
     */
    public function test_asking_for_the_lesson_teaches_the_same_layer_and_retires_the_offer(): void
    {
        $subject = $this->descent()->start(null, 'Pure functions', approach: Conversation::APPROACH_QUESTION);
        $this->open($subject);

        $this->llm->verdict = CheckpointAttempt::VERDICT_RETRY;
        $this->descent()->gradeCheckpoint($subject->fresh(), 'No idea, honestly.');

        $subject->refresh();
        $this->assertSame(0, $subject->current_depth);
        $this->assertTrue($this->descent()->awaitsTeaching($subject));

        $this->assertSame(Message::PHASE_TEACH, $this->open($subject));

        $this->assertSame(0, $subject->current_depth, 'Being taught is not progress.');
        $this->assertFalse($this->descent()->awaitsTeaching($subject), 'The lesson is no longer owed.');
    }

    public function test_the_next_layer_is_posed_again_once_the_current_one_is_cleared(): void
    {
        config(['platform.chat.max_depth' => 9]);

        $subject = $this->descent()->start(null, 'Pure functions', approach: Conversation::APPROACH_QUESTION);
        $this->open($subject);
        $this->descent()->gradeCheckpoint($subject->fresh(), 'Same input, same output.');

        $subject->refresh();
        $this->assertSame(1, $subject->current_depth);
        $this->assertSame(Message::PHASE_QUESTION, $this->open($subject));
    }

    /** A reframe is a re-teach, so it never counts as the cold question. */
    public function test_a_reframe_always_teaches(): void
    {
        $subject = $this->descent()->start(null, 'Pure functions', approach: Conversation::APPROACH_QUESTION);

        $this->assertSame(
            Message::PHASE_TEACH,
            $this->descent()->turnPhase($subject, DescentService::REFRAME_ANALOGY),
        );
    }

    /**
     * Switching mid-descent applies to the next layer, never to the one already
     * open — the checkpoint on screen is not rewritten under the learner.
     */
    public function test_switching_the_approach_leaves_the_open_layer_alone(): void
    {
        $user = User::factory()->create();
        $subject = $this->descent()->start($user, 'Pure functions');
        $this->open($subject);

        $this->actingAs($user)
            ->patch(route('subject.approach', $subject), ['approach' => Conversation::APPROACH_QUESTION])
            ->assertRedirect();

        $subject->refresh();
        $this->assertSame(Conversation::APPROACH_QUESTION, $subject->approach);
        $this->assertSame(Message::PHASE_TEACH, $this->descent()->turnPhase($subject), 'Layer 0 is already open.');
        $this->assertFalse($this->descent()->awaitsTeaching($subject), 'It was taught, so nothing is owed.');

        // It takes effect on the layer after this one.
        $this->descent()->gradeCheckpoint($subject->fresh(), 'Same input, same output.');
        $this->assertSame(Message::PHASE_QUESTION, $this->descent()->turnPhase($subject->fresh()));
    }

    public function test_the_composer_remembers_the_last_approach_a_learner_chose(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('descend'), [
            'prompt' => 'Category theory',
            'approach' => Conversation::APPROACH_QUESTION,
        ])->assertRedirect();

        $this->assertSame(Conversation::APPROACH_QUESTION, $user->fresh()->preferred_approach);

        // The next subject follows the remembered choice without being told again.
        $this->actingAs($user)->post(route('descend'), ['prompt' => 'Sheaves'])->assertRedirect();

        $this->assertSame(
            Conversation::APPROACH_QUESTION,
            Conversation::query()->latest('id')->first()->approach,
        );
    }

    public function test_the_approach_switch_refuses_a_subject_that_is_not_yours(): void
    {
        $subject = $this->descent()->start(User::factory()->create(), 'Pure functions');

        $this->actingAs(User::factory()->create())
            ->patch(route('subject.approach', $subject), ['approach' => Conversation::APPROACH_QUESTION])
            ->assertRedirect(route('home'));

        $this->assertSame(Conversation::APPROACH_GUIDED, $subject->fresh()->approach);
    }

    /** Both kinds of opening turn end on a checkpoint, so both are gradeable. */
    public function test_the_checkpoint_is_read_from_a_posed_layer_as_well_as_a_taught_one(): void
    {
        $subject = $this->descent()->start(null, 'Pure functions', approach: Conversation::APPROACH_QUESTION);
        $this->open($subject);

        $this->assertSame('Explain it back.', $this->descent()->currentCheckpoint($subject));
    }
}
