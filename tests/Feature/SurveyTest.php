<?php

namespace Tests\Feature;

use App\Contracts\LlmClient;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\Chat\DescentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FakeLlmClient;
use Tests\TestCase;

/**
 * SQ3R's first step, and the one this product had been skipping: the ground is
 * surveyed before the first layer opens into it.
 *
 * Three things are worth pinning. It happens only for a subject that has a page
 * to survey, because the map is a map of that page. It happens once, before any
 * layer — including for subjects that were already underway when it shipped,
 * who must not be handed a map of ground they have walked. And it leaves **no
 * checkpoint**: a survey proves nothing and costs no depth, so a subject that
 * came out of one is standing exactly where it went in.
 */
class SurveyTest extends TestCase
{
    use RefreshDatabase;

    private FakeLlmClient $llm;

    protected function setUp(): void
    {
        parent::setUp();

        $this->llm = new FakeLlmClient(chunks: ['The page covers three things.']);
        $this->app->instance(LlmClient::class, $this->llm);
    }

    private function descent(): DescentService
    {
        return app(DescentService::class);
    }

    private function grounded(?User $user = null): Conversation
    {
        $subject = $this->descent()->start($user, 'Attention is all you need');

        $subject->sources()->create([
            'url' => 'https://example.test/paper',
            'title' => 'Attention Is All You Need',
            'site' => 'example.test',
            'text' => str_repeat('transformer attention encoder decoder ', 200),
            'words' => 1_000,
        ]);

        return $subject->refresh();
    }

    /** One turn, with the phase derived exactly as the stream derives it. */
    private function turn(Conversation $conversation, ?string $reframe = null): string
    {
        $phase = $this->descent()->turnPhase($conversation, $reframe);
        $stream = $this->llm->streamTeachingTurn($this->descent()->teachingRequest($conversation, $reframe, $phase));

        foreach ($stream as $chunk) {
        }

        $this->descent()->applyTeachingTurn($conversation, $stream, $phase);
        $conversation->refresh();

        return $phase;
    }

    public function test_a_grounded_subject_surveys_the_page_before_the_first_layer(): void
    {
        $subject = $this->grounded();

        $this->assertTrue($this->descent()->awaitsSurvey($subject));
        $this->assertSame(Message::PHASE_SURVEY, $this->turn($subject));

        // A survey proves nothing: no checkpoint, no depth, nothing to answer.
        $this->assertSame(0, $subject->current_depth);
        $this->assertSame(Conversation::STATUS_EXPLORING, $subject->status);
        $this->assertFalse($subject->isCheckpointPending());
        $this->assertSame('', $this->descent()->currentCheckpoint($subject));

        // And it happens once — the next turn is the layer it mapped.
        $this->assertFalse($this->descent()->awaitsSurvey($subject));
        $this->assertSame(Message::PHASE_TEACH, $this->turn($subject));
        $this->assertTrue($subject->isCheckpointPending());
    }

    public function test_a_subject_with_no_page_is_never_surveyed(): void
    {
        $subject = $this->descent()->start(null, 'Pure functions');

        $this->assertFalse($this->descent()->awaitsSurvey($subject));
        $this->assertSame(Message::PHASE_TEACH, $this->turn($subject));
    }

    /**
     * The one that protects existing learners: a grounded subject already past
     * its first layer must never be interrupted by a map of where it has been.
     */
    public function test_a_descent_already_under_way_is_not_surveyed(): void
    {
        $subject = $this->grounded();

        // Layer 00 opened without a survey — every source subject that existed
        // before this feature shipped looks exactly like this.
        $stream = $this->llm->streamTeachingTurn($this->descent()->teachingRequest($subject));

        foreach ($stream as $chunk) {
        }

        $this->descent()->applyTeachingTurn($subject, $stream, Message::PHASE_TEACH);

        $this->assertFalse($this->descent()->awaitsSurvey($subject->refresh()));
        $this->assertSame(Message::PHASE_TEACH, $this->descent()->turnPhase($subject));
    }

    /** A reframe re-teaches the layer. It must never be answered with a map. */
    public function test_a_reframe_is_never_a_survey(): void
    {
        $subject = $this->grounded();

        $this->assertSame(Message::PHASE_TEACH, $this->descent()->turnPhase($subject, DescentService::REFRAME_ANALOGY));
    }

    public function test_the_survey_directive_forbids_teaching_and_carries_the_source(): void
    {
        $subject = $this->grounded();
        $this->turn($subject);

        $messages = $this->llm->lastTeachingRequest->messages;
        $directive = $messages[count($messages) - 1]['content'];

        $this->assertStringContainsString('NOT a teaching turn', $directive);
        $this->assertStringContainsString('Do NOT write a `**Checkpoint:**` line', $directive);
        // Same assembler as every other turn: language and source travel with it.
        $this->assertStringContainsString('[LANGUAGE]', $directive);
        $this->assertStringContainsString('[SOURCE]', $directive);
    }

    public function test_the_workspace_offers_the_survey_and_names_the_method(): void
    {
        $user = User::factory()->create();
        $subject = $this->grounded($user);

        $this->actingAs($user)->get(route('subject.show', $subject))
            ->assertOk()
            ->assertSee(__('frontend.chat.survey-cta'))
            ->assertSee('data-awaits-survey="1"', false);

        $this->turn($subject);

        $this->actingAs($user)->get(route('subject.show', $subject))
            ->assertOk()
            ->assertSee(__('frontend.chat.survey'))
            ->assertSee(route('methods.show', 'sq3r'))
            ->assertSee('data-awaits-survey="0"', false);
    }
}
