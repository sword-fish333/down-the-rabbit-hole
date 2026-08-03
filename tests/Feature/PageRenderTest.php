<?php

namespace Tests\Feature;

use App\Contracts\LlmClient;
use App\Models\Admin;
use App\Models\Concept;
use App\Models\Conversation;
use App\Models\LearningMode;
use App\Models\User;
use App\Services\Chat\DescentService;
use Database\Seeders\LearningModeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\FakeLlmClient;
use Tests\TestCase;

/**
 * Renders every page for real.
 *
 * A Blade view that compiles can still explode on a missing translation key or
 * an undefined variable, and those only surface at render time — which is why
 * this walks the whole surface rather than trusting compilation.
 */
class PageRenderTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Admin $admin;

    private Conversation $hole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(LlmClient::class, new FakeLlmClient);
        $this->seed(LearningModeSeeder::class);

        $this->user = User::factory()->create(['email' => 'learner@example.com']);
        $this->admin = Admin::create([
            'name' => 'Master',
            'first_name' => 'Master',
            'email' => 'master@admin.test',
            'password' => 'password',
            'role' => Admin::ROLES[0],
            'enabled' => true,
        ]);

        // A hole with real content, so the transcript and mastery map render
        // against something rather than an empty state.
        $descent = app(DescentService::class);
        $this->hole = $descent->start($this->user, 'Pure functions');

        $request = $descent->teachingRequest($this->hole);
        $stream = app(LlmClient::class)->streamTeachingTurn($request);
        foreach ($stream as $chunk) {
        }
        $descent->applyTeachingTurn($this->hole, $stream);
        $descent->gradeCheckpoint($this->hole->fresh(), 'Same input, same output, no side effects.', 80);

        $this->hole->refresh();
    }

    public static function guestPages(): array
    {
        return [
            'home' => ['/'],
            'login' => ['/login'],
            'register' => ['/register'],
        ];
    }

    #[DataProvider('guestPages')]
    public function test_guest_pages_render(string $uri): void
    {
        $this->get($uri)->assertOk();
    }

    public function test_the_learner_pages_render(): void
    {
        $this->actingAs($this->user)
            ->get(route('subjects.index'))->assertOk()->assertSee('Pure functions');

        $this->actingAs($this->user)
            ->get(route('profile.index'))->assertOk();

        $this->actingAs($this->user)
            ->get(route('subject.show', $this->hole))->assertOk()
            ->assertSee('Pure functions')
            ->assertSee('Layer cleared');
    }

    public function test_the_verification_notice_renders_for_an_unverified_learner(): void
    {
        $unverified = User::factory()->unverified()->create();

        $this->actingAs($unverified)->get(route('verification.notice'))->assertOk();
    }

    public function test_a_verified_learner_is_sent_past_the_notice(): void
    {
        $this->actingAs($this->user)
            ->get(route('verification.notice'))
            ->assertRedirect(route('subjects.index'));
    }

    public function test_the_home_page_offers_a_resumable_hole(): void
    {
        $this->actingAs($this->user)->get('/')->assertOk()->assertSee('Resume');
    }

    public static function adminPages(): array
    {
        return [
            'dashboard' => ['admin.dashboard'],
            'profile' => ['admin.profile.index'],
            'learning modes' => ['admin.learning-mode.index'],
            'new learning mode' => ['admin.learning-mode.create'],
            'learners' => ['admin.user.index'],
            'rabbit holes' => ['admin.conversation.index'],
        ];
    }

    #[DataProvider('adminPages')]
    public function test_admin_index_pages_render(string $route): void
    {
        $this->actingAs($this->admin, 'admin')->get(route($route))->assertOk();
    }

    public function test_admin_detail_pages_render(): void
    {
        $mode = LearningMode::firstOrFail();

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.learning-mode.edit', $mode))->assertOk()->assertSee($mode->name);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.user.edit', $this->user))->assertOk();

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.conversation.edit', $this->hole))->assertOk()->assertSee('Pure functions');
    }

    public function test_the_mastery_map_renders_its_states(): void
    {
        Concept::create([
            'conversation_id' => $this->hole->id,
            'name' => 'Referential transparency',
            'slug' => 'referential-transparency',
            'state' => Concept::STATE_MISUNDERSTOOD,
            'first_seen_depth' => 0,
        ]);

        $this->actingAs($this->user)
            ->get(route('subject.show', $this->hole))
            ->assertOk()
            ->assertSee('Referential transparency')
            ->assertSee('Misunderstood');
    }
}
