<?php

namespace Tests\Feature;

use App\DTOs\LlmStreamResult;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Models\XpEvent;
use App\Services\Chat\DescentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Drives the descent state machine offline — assistant turns are fed in as
 * LlmStreamResult, so no API key or network is needed.
 */
class DescentTest extends TestCase
{
    use RefreshDatabase;

    private function descent(): DescentService
    {
        return app(DescentService::class);
    }

    private function teachResult(): LlmStreamResult
    {
        return new LlmStreamResult("Here is the layer.\n\n```json\n{\"phase\":\"checkpoint\"}\n```", 'claude-haiku-4-5');
    }

    private function gradeResult(string $verdict): LlmStreamResult
    {
        return new LlmStreamResult("Feedback.\n\n```json\n{\"phase\":\"grade\",\"verdict\":\"{$verdict}\"}\n```", 'claude-haiku-4-5');
    }

    public function test_a_teaching_turn_opens_a_checkpoint(): void
    {
        $descent = $this->descent();
        $conversation = $descent->start(null, 'Quantum entanglement');

        $this->assertSame(Conversation::STATUS_EXPLORING, $conversation->status);
        $this->assertSame(1, $conversation->messages()->count());

        $plan = $descent->buildTurnPlan($conversation);
        $this->assertSame(Message::PHASE_TEACH, $plan->phase);

        $descent->applyAssistantTurn($conversation, $plan, $this->teachResult());

        $this->assertSame(Conversation::STATUS_CHECKPOINT_PENDING, $conversation->fresh()->status);
    }

    public function test_passing_a_checkpoint_descends_a_layer(): void
    {
        $descent = $this->descent();
        $conversation = $descent->start(null, 'Stoicism');
        $descent->applyAssistantTurn($conversation, $descent->buildTurnPlan($conversation), $this->teachResult());

        $conversation->refresh();
        $descent->recordUserProof($conversation, 'My explanation in my own words.');

        $gradePlan = $descent->buildTurnPlan($conversation);
        $this->assertSame(Message::PHASE_GRADE, $gradePlan->phase);

        $descent->applyAssistantTurn($conversation, $gradePlan, $this->gradeResult('pass'));

        $conversation->refresh();
        $this->assertSame(1, $conversation->current_depth);
        $this->assertSame(Conversation::STATUS_EXPLORING, $conversation->status);
    }

    public function test_a_retry_keeps_the_learner_on_the_layer(): void
    {
        $descent = $this->descent();
        $conversation = $descent->start(null, 'The fall of Rome');
        $descent->applyAssistantTurn($conversation, $descent->buildTurnPlan($conversation), $this->teachResult());
        $conversation->refresh();
        $descent->recordUserProof($conversation, 'A weak answer.');
        $descent->applyAssistantTurn($conversation, $descent->buildTurnPlan($conversation), $this->gradeResult('retry'));

        $conversation->refresh();
        $this->assertSame(0, $conversation->current_depth);
        $this->assertSame(Conversation::STATUS_CHECKPOINT_PENDING, $conversation->status);
    }

    public function test_a_missing_control_block_fails_safe_to_retry(): void
    {
        $descent = $this->descent();
        $conversation = $descent->start(null, 'Chess');
        $descent->applyAssistantTurn($conversation, $descent->buildTurnPlan($conversation), $this->teachResult());
        $conversation->refresh();
        $descent->recordUserProof($conversation, 'answer');

        // Grade turn with no control block at all.
        $descent->applyAssistantTurn(
            $conversation,
            $descent->buildTurnPlan($conversation),
            new LlmStreamResult('I forgot the control block.', 'claude-haiku-4-5'),
        );

        $this->assertSame(Conversation::STATUS_CHECKPOINT_PENDING, $conversation->fresh()->status);
    }

    public function test_model_routes_by_depth_and_phase(): void
    {
        $descent = $this->descent();
        config(['platform.chat.deep_threshold' => 3]);

        $models = config('platform.chat.models.'.config('platform.chat.provider'));

        $this->assertSame($models['mid'], $descent->pickModel(0, Message::PHASE_TEACH));
        $this->assertSame($models['cheap'], $descent->pickModel(0, Message::PHASE_GRADE));
        $this->assertSame($models['deep'], $descent->pickModel(3, Message::PHASE_TEACH));
    }

    public function test_an_authenticated_pass_awards_xp_and_streak(): void
    {
        $user = User::create(['name' => 'Alice', 'email' => 'alice@example.com', 'password' => 'secret-pass']);

        $descent = $this->descent();
        $conversation = $descent->start($user, 'Neuroscience');
        $descent->applyAssistantTurn($conversation, $descent->buildTurnPlan($conversation), $this->teachResult());
        $conversation->refresh();
        $descent->recordUserProof($conversation, 'A sound explanation.');
        $descent->applyAssistantTurn($conversation, $descent->buildTurnPlan($conversation), $this->gradeResult('pass'));

        $this->assertSame((int) config('platform.chat.layer_xp'), $user->fresh()->xp);
        $this->assertSame(1, XpEvent::where('user_id', $user->id)->count());
        $this->assertSame(1, (int) $user->streak()->first()->current_count);
    }

    public function test_a_guest_pass_awards_nothing(): void
    {
        $descent = $this->descent();
        $conversation = $descent->start(null, 'Go');
        $descent->applyAssistantTurn($conversation, $descent->buildTurnPlan($conversation), $this->teachResult());
        $conversation->refresh();
        $descent->recordUserProof($conversation, 'answer');
        $descent->applyAssistantTurn($conversation, $descent->buildTurnPlan($conversation), $this->gradeResult('pass'));

        $this->assertSame(0, XpEvent::count());
    }
}
