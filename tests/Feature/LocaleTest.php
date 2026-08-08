<?php

namespace Tests\Feature;

use App\Contracts\LlmClient;
use App\Models\User;
use App\Services\Chat\DescentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Tests\Support\FakeLlmClient;
use Tests\TestCase;

/**
 * Guards both halves of the localisation contract.
 *
 * The interface half is a *silent* failure: `fallback_locale` means a key
 * missing from lang/ro renders the English string, so a half-translated page
 * looks deliberate and ships. Parity is asserted, not eyeballed.
 *
 * The teaching half is the one learners actually notice — a Romanian learner
 * being taught in English — so the language directive and the per-subject
 * locale are pinned here too.
 */
class LocaleTest extends TestCase
{
    use RefreshDatabase;

    private FakeLlmClient $llm;

    protected function setUp(): void
    {
        parent::setUp();

        $this->llm = new FakeLlmClient;
        $this->app->instance(LlmClient::class, $this->llm);
    }

    /**
     * Every key the English copy defines must exist in every other language.
     * Extra keys are fine — lang/ro carries `modes.*` on purpose, because in
     * English those strings come from the database.
     */
    public function test_every_locale_translates_every_english_key(): void
    {
        $english = Arr::dot(require lang_path('en/frontend.php'));

        foreach (array_keys(config('platform.locales')) as $locale) {
            if ($locale === 'en') {
                continue;
            }

            $translated = Arr::dot(require lang_path("{$locale}/frontend.php"));
            $missing = array_diff(array_keys($english), array_keys($translated));

            $this->assertSame([], array_values($missing),
                "lang/{$locale}/frontend.php is missing:\n".implode("\n", $missing));
        }
    }

    /**
     * Placeholders are the other silent failure: a translation that drops
     * `:count` renders a sentence with a hole in it, and no test of key names
     * would see it.
     */
    public function test_translations_keep_their_placeholders(): void
    {
        $english = Arr::dot(require lang_path('en/frontend.php'));

        foreach (array_keys(config('platform.locales')) as $locale) {
            if ($locale === 'en') {
                continue;
            }

            $translated = Arr::dot(require lang_path("{$locale}/frontend.php"));

            foreach ($english as $key => $line) {
                if (! is_string($line) || ! isset($translated[$key]) || ! is_string($translated[$key])) {
                    continue;
                }

                preg_match_all('/:[a-z_]+/', $line, $expected);
                preg_match_all('/:[a-z_]+/', $translated[$key], $actual);

                $this->assertSame(
                    array_unique($expected[0]),
                    array_unique($actual[0]),
                    "Placeholders differ in {$locale} for frontend.{$key}",
                );
            }
        }
    }

    public function test_the_switcher_remembers_the_choice_and_rejects_unknown_languages(): void
    {
        $this->get(route('locale.switch', 'ro'))
            ->assertRedirect()
            ->assertSessionHas('locale', 'ro')
            ->assertCookie('locale', 'ro');

        $this->get(route('locale.switch', 'de'))->assertNotFound();
    }

    /**
     * Romanian counts in three forms, and the third one — the „de" above 19 — is
     * the one a machine translation always misses. Explicit ranges in the lang
     * files do the work; this proves they are wired to the right numbers.
     */
    public function test_romanian_plurals_use_all_three_forms(): void
    {
        $this->app->setLocale('ro');

        $this->assertSame('1 concept stăpânit', trans_choice('frontend.subjects.mastered', 1, ['count' => 1]));
        $this->assertSame('5 concepte stăpânite', trans_choice('frontend.subjects.mastered', 5, ['count' => 5]));
        $this->assertSame('20 de concepte stăpânite', trans_choice('frontend.subjects.mastered', 20, ['count' => 20]));
    }

    /**
     * The language belongs to the account, not to the browser: a learner who
     * chose Romanian on their laptop opens the app on a phone in Romanian.
     */
    public function test_a_signed_in_learner_carries_their_language_to_a_new_device(): void
    {
        $user = User::factory()->create(['locale' => 'ro']);

        $this->actingAs($user)->get('/')->assertSee('<html lang="ro"', false);

        // Switching persists to the account, not just to this session.
        $this->actingAs($user)->get(route('locale.switch', 'en'))->assertRedirect();
        $this->assertSame('en', $user->fresh()->locale);
    }

    public function test_a_romanian_browser_gets_a_romanian_page_without_choosing(): void
    {
        $this->get('/', ['Accept-Language' => 'ro-RO,ro;q=0.9,en;q=0.8'])
            ->assertOk()
            ->assertSee('<html lang="ro"', false)
            ->assertSee(__('frontend.home.cta', locale: 'ro'));
    }

    public function test_the_admin_panel_ignores_the_learner_language(): void
    {
        $this->withSession(['locale' => 'ro'])->get(route('admin.login'))
            ->assertOk()
            ->assertSee('<html lang="en"', false);
    }

    /**
     * A subject keeps the language it was opened in. Without this, a learner who
     * switches the interface to English mid-descent gets layer 4 in English on
     * top of three Romanian ones.
     */
    public function test_a_subject_is_taught_in_the_language_it_was_opened_in(): void
    {
        $this->app->setLocale('ro');
        $subject = app(DescentService::class)->start(User::factory()->create(), 'Funcții pure');

        $this->assertSame('ro', $subject->locale);
        $this->assertSame('Romanian', $subject->language());

        $this->app->setLocale('en');
        $messages = app(DescentService::class)->teachingRequest($subject->fresh())->messages;

        $this->assertStringContainsString('[LANGUAGE] Write this entire turn in Romanian',
            implode("\n", array_column($messages, 'content')));
    }

    /** The verdict is prose the learner reads, so it follows the same language. */
    public function test_the_verdict_is_graded_in_the_language_of_the_subject(): void
    {
        $this->app->setLocale('ro');

        $descent = app(DescentService::class);
        $subject = $descent->start(User::factory()->create(), 'Funcții pure');

        $stream = $this->llm->streamTeachingTurn($descent->teachingRequest($subject));
        foreach ($stream as $chunk) {
            // Draining the stream is what leaves a checkpoint to grade.
        }
        $descent->applyTeachingTurn($subject, $stream);

        $descent->gradeCheckpoint($subject->fresh(), 'Aceeași intrare, aceeași ieșire.');

        $this->assertSame('Romanian', $this->llm->lastGradingRequest->language);
    }
}
