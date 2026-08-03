<?php

namespace Tests\Feature;

use App\Contracts\LlmClient;
use App\Models\Admin;
use App\Models\Conversation;
use App\Models\LearningMode;
use App\Models\SubjectFolder;
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

        $subject = $this->subjectWithContent($user);
        $unresolved = [];

        foreach ($this->learnerPages($user, $subject) as [$uri, $actor]) {
            $response = $actor ? $this->actingAs($actor)->get($uri) : $this->get($uri);
            $unresolved = [...$unresolved, ...$this->keysIn($uri, $response->getContent())];
        }

        foreach ($this->adminPages($user, $subject) as $uri) {
            $response = $this->actingAs($admin, 'admin')->get($uri);
            $unresolved = [...$unresolved, ...$this->keysIn($uri, $response->getContent())];
        }

        $this->assertSame(
            [],
            array_values(array_unique($unresolved)),
            "Unresolved translation keys reached the page:\n".implode("\n", array_unique($unresolved)),
        );
    }

    /**
     * A subject far enough along that transcript, verdict and mastery map all
     * render — filed in a folder and shared, so the library, the organiser and
     * the public page all have something real to show.
     */
    private function subjectWithContent(User $user): Conversation
    {
        $descent = app(DescentService::class);
        $subject = $descent->start($user, 'Pure functions');

        $stream = app(LlmClient::class)->streamTeachingTurn($descent->teachingRequest($subject));
        foreach ($stream as $chunk) {
        }
        $descent->applyTeachingTurn($subject, $stream);
        $descent->gradeCheckpoint($subject->fresh(), 'Same input, same output.', 80);

        $folder = SubjectFolder::create(['user_id' => $user->id, 'name' => 'Functional programming']);
        SubjectFolder::create(['user_id' => $user->id, 'parent_id' => $folder->id, 'name' => 'Laws']);

        $subject = $subject->fresh();
        $subject->update(['folder_id' => $folder->id]);
        $subject->sources()->create([
            'url' => 'https://example.com/pure-functions',
            'title' => 'Pure functions, explained',
            'site' => 'example.com',
            'text' => str_repeat('A pure function returns the same output for the same input. ', 30),
            'words' => 300,
        ]);
        $subject->share();

        return $subject->fresh();
    }

    /**
     * @return array<int, array{0: string, 1: ?User}>
     */
    private function learnerPages(User $user, Conversation $subject): array
    {
        return [
            ['/', null],
            ['/login', null],
            ['/register', null],
            [route('subjects.index'), $user],
            [route('subjects.index', ['q' => 'nothing here', 'filter' => 'shared']), $user],
            [route('subjects.organization'), $user],
            [route('profile.index'), $user],
            [route('subject.show', $subject), $user],
            [route('subject.shared', $subject->share_token), null],
        ];
    }

    /**
     * @return array<int, string>
     */
    private function adminPages(User $user, Conversation $subject): array
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
            route('admin.conversation.edit', $subject),
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
