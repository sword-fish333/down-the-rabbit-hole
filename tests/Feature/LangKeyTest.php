<?php

namespace Tests\Feature;

use App\Contracts\LlmClient;
use App\Models\Admin;
use App\Models\Conversation;
use App\Models\LearningMode;
use App\Models\User;
use App\Services\Chat\DescentService;
use Database\Seeders\LearningModeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FakeLlmClient;
use Tests\TestCase;

/**
 * Guards the localisation contract.
 *
 * A missing translation key doesn't throw — Laravel renders the key itself, so
 * `frontend.chat.go-deeper` can ship to production looking like a bug nobody
 * noticed. Since every user-facing string here goes through `__()`, a raw key
 * in rendered HTML is always a defect; this asserts there are none.
 */
class LangKeyTest extends TestCase
{
    use RefreshDatabase;

    /** Anything shaped like `namespace.some.key` that survived to the output. */
    private const UNRESOLVED = '/\b(frontend|admin\/frontend|admin\/backend)\.[a-z0-9_\-]+(\.[a-z0-9_\-]+)+/i';

    public function test_no_unresolved_translation_keys_are_rendered(): void
    {
        $this->app->instance(LlmClient::class, new FakeLlmClient);
        $this->seed(LearningModeSeeder::class);

        $user = User::factory()->create();
        $admin = Admin::create([
            'name' => 'Master',
            'first_name' => 'Master',
            'email' => 'master@admin.test',
            'password' => 'password',
            'role' => Admin::ROLES[0],
            'enabled' => true,
        ]);

        $hole = $this->holeWithContent($user);
        $unresolved = [];

        foreach ($this->learnerPages($user, $hole) as [$uri, $actor]) {
            $response = $actor ? $this->actingAs($actor)->get($uri) : $this->get($uri);
            $unresolved = [...$unresolved, ...$this->keysIn($uri, $response->getContent())];
        }

        foreach ($this->adminPages($user, $hole) as $uri) {
            $response = $this->actingAs($admin, 'admin')->get($uri);
            $unresolved = [...$unresolved, ...$this->keysIn($uri, $response->getContent())];
        }

        $this->assertSame(
            [],
            array_values(array_unique($unresolved)),
            "Unresolved translation keys reached the page:\n".implode("\n", array_unique($unresolved)),
        );
    }

    /** A hole far enough along that transcript, verdict and mastery map all render. */
    private function holeWithContent(User $user): Conversation
    {
        $descent = app(DescentService::class);
        $hole = $descent->start($user, 'Pure functions');

        $stream = app(LlmClient::class)->streamTeachingTurn($descent->teachingRequest($hole));
        foreach ($stream as $chunk) {
        }
        $descent->applyTeachingTurn($hole, $stream);
        $descent->gradeCheckpoint($hole->fresh(), 'Same input, same output.', 80);

        return $hole->fresh();
    }

    /**
     * @return array<int, array{0: string, 1: ?User}>
     */
    private function learnerPages(User $user, Conversation $hole): array
    {
        return [
            ['/', null],
            ['/login', null],
            ['/register', null],
            [route('holes.index'), $user],
            [route('profile.index'), $user],
            [route('hole.show', $hole), $user],
        ];
    }

    /**
     * @return array<int, string>
     */
    private function adminPages(User $user, Conversation $hole): array
    {
        return [
            route('admin.dashboard'),
            route('admin.profile.index'),
            route('admin.learning-mode.index'),
            route('admin.learning-mode.create'),
            route('admin.learning-mode.edit', LearningMode::firstOrFail()),
            route('admin.user.index'),
            route('admin.user.edit', $user),
            route('admin.conversation.index'),
            route('admin.conversation.edit', $hole),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function keysIn(string $uri, string $html): array
    {
        preg_match_all(self::UNRESOLVED, $html, $matches);

        return array_map(fn (string $key) => "{$uri} => {$key}", $matches[0]);
    }
}
