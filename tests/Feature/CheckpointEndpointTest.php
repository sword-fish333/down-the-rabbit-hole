<?php

namespace Tests\Feature;

use App\Contracts\LlmClient;
use App\Models\CheckpointAttempt;
use App\Models\Conversation;
use App\Models\LearningMode;
use App\Models\User;
use App\Services\Chat\DescentService;
use Database\Seeders\LearningModeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FakeLlmClient;
use Tests\TestCase;

/**
 * The HTTP surface of the descent: who can reach a hole, and what the browser
 * gets back when a proof is submitted.
 */
class CheckpointEndpointTest extends TestCase
{
    use RefreshDatabase;

    private FakeLlmClient $llm;

    protected function setUp(): void
    {
        parent::setUp();

        $this->llm = new FakeLlmClient;
        $this->app->instance(LlmClient::class, $this->llm);
    }

    /** A hole sitting on an open checkpoint, ready to be answered. */
    private function holeAwaitingProof(?User $user = null): Conversation
    {
        $descent = app(DescentService::class);
        $conversation = $descent->start($user, 'Pure functions');

        $stream = $this->llm->streamTeachingTurn($descent->teachingRequest($conversation));
        foreach ($stream as $chunk) {
        }
        $descent->applyTeachingTurn($conversation, $stream);

        return $conversation->refresh();
    }

    public function test_submitting_a_proof_returns_the_structured_verdict(): void
    {
        $user = User::factory()->create();
        $hole = $this->holeAwaitingProof($user);

        $response = $this->actingAs($user)->postJson(route('subject.checkpoint', $hole), [
            'message' => 'Same input, same output, and it touches nothing outside itself.',
            'self_rating' => 60,
        ]);

        $response->assertOk()
            ->assertJsonPath('attempt.verdict', CheckpointAttempt::VERDICT_PASS)
            ->assertJsonPath('attempt.score', 86)
            ->assertJsonPath('state.passed', true)
            ->assertJsonPath('state.depth', 1)
            ->assertJsonStructure([
                'state' => ['status', 'depth', 'max_depth', 'passed', 'surfaced', 'mastery'],
                'attempt' => ['verdict', 'score', 'feedback', 'criteria', 'demonstrated_concepts', 'misconceptions', 'calibration_gap'],
            ]);
    }

    public function test_a_proof_is_rejected_when_no_checkpoint_is_open(): void
    {
        $user = User::factory()->create();
        $hole = app(DescentService::class)->start($user, 'Chess'); // never taught

        $this->actingAs($user)
            ->postJson(route('subject.checkpoint', $hole), ['message' => 'an answer'])
            ->assertStatus(409);
    }

    public function test_another_learners_hole_is_not_reachable(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $hole = $this->holeAwaitingProof($owner);

        $this->actingAs($intruder)->get(route('subject.show', $hole))->assertRedirect(route('home'));

        $this->actingAs($intruder)
            ->postJson(route('subject.checkpoint', $hole), ['message' => 'an answer'])
            ->assertStatus(403);
    }

    public function test_a_guest_hole_is_reachable_only_within_its_own_session(): void
    {
        $this->post(route('descend'), ['prompt' => 'Stoicism'])->assertRedirect();

        $hole = Conversation::firstOrFail();
        $this->assertNull($hole->user_id);

        // Same session: allowed.
        $this->get(route('subject.show', $hole))->assertOk();

        // A different session has no claim on it.
        $this->flushSession();
        $this->get(route('subject.show', $hole))->assertRedirect(route('home'));
    }

    public function test_the_daily_limit_closes_the_stream_with_an_error_event(): void
    {
        config(['platform.chat.guest_daily_limit' => 0]);

        $this->post(route('descend'), ['prompt' => 'Stoicism']);
        $hole = Conversation::firstOrFail();

        $response = $this->get(route('subject.stream', $hole));

        $response->assertOk();
        // The browser gets a normal `error` frame rather than a dropped
        // connection it would have to guess about.
        $this->assertStringContainsString('event: error', $response->streamedContent());
    }

    public function test_starting_a_descent_records_the_chosen_learning_mode(): void
    {
        $this->seed(LearningModeSeeder::class);
        $mode = LearningMode::where('slug', 'exam-preparation')->firstOrFail();

        $this->post(route('descend'), [
            'prompt' => 'Thermodynamics',
            'learning_mode_id' => $mode->id,
        ])->assertRedirect();

        $this->assertSame($mode->id, Conversation::firstOrFail()->learning_mode_id);
    }
}
