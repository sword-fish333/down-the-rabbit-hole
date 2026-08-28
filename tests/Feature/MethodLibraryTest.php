<?php

namespace Tests\Feature;

use App\Contracts\LlmClient;
use App\Models\MethodNote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FakeLlmClient;
use Tests\TestCase;

/**
 * The reference desk.
 *
 * Two things are worth pinning here and nothing else is. An entry is written
 * once per language and then read from the table — if that ever stops holding,
 * a public page starts billing a model per pageview. And the reading list is
 * never the model's: it comes from config and must survive the model being
 * unreachable, because a page whose whole claim is "go and check" cannot lose
 * its citations to a timeout.
 */
class MethodLibraryTest extends TestCase
{
    use RefreshDatabase;

    private FakeLlmClient $llm;

    protected function setUp(): void
    {
        parent::setUp();

        $this->llm = new FakeLlmClient(chunks: ['## What it is', "\n\nA written entry."]);
        $this->app->instance(LlmClient::class, $this->llm);
    }

    public function test_an_entry_is_written_once_and_then_served_from_the_table(): void
    {
        $this->get(route('methods.show', 'sq3r'))
            ->assertOk()
            ->assertSee('A written entry.');

        $this->assertSame(1, $this->llm->teachCalls);
        $this->assertDatabaseHas('method_notes', ['slug' => 'sq3r', 'locale' => 'en']);

        $this->get(route('methods.show', 'sq3r'))->assertOk()->assertSee('A written entry.');

        $this->assertSame(1, $this->llm->teachCalls);
    }

    public function test_each_language_gets_its_own_entry(): void
    {
        $this->get(route('methods.show', 'sq3r'))->assertOk();
        $this->withSession(['locale' => 'ro'])->get(route('methods.show', 'sq3r'))->assertOk();

        $this->assertSame(2, $this->llm->teachCalls);
        $this->assertSame(['en', 'ro'], MethodNote::query()->orderBy('locale')->pluck('locale')->all());
    }

    public function test_the_reading_list_survives_the_model_being_unreachable(): void
    {
        // The one failure mode that matters: no prose, but the page still has
        // its title, its summary and — the point of the page — its citations.
        $this->app->instance(LlmClient::class, new FakeLlmClient(chunks: []));

        $this->get(route('methods.show', 'retrieval-practice'))
            ->assertOk()
            ->assertSee(__('frontend.methods.unavailable'))
            ->assertSee('Roediger')
            ->assertSee('doi.org', false);

        $this->assertDatabaseCount('method_notes', 0);
    }

    public function test_only_registered_methods_have_a_page(): void
    {
        $this->get(route('methods.show', 'made-up-technique'))->assertNotFound();
        $this->get(route('methods.index'))->assertOk()->assertSee(__('frontend.methods.items.sq3r.name'));
    }
}
