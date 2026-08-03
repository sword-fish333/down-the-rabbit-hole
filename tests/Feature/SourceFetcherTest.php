<?php

namespace Tests\Feature;

use App\Contracts\LlmClient;
use App\Models\User;
use App\Services\Chat\DescentService;
use App\Services\Chat\SourceFetcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\FakeLlmClient;
use Tests\TestCase;

/**
 * Studying a web page.
 *
 * The load-bearing test here is the SSRF one: the URL comes from a visitor and
 * the fetch runs inside the network, so a refused host must be refused *before*
 * a socket is opened — asserting "no request was sent" is the whole point.
 *
 * Hosts are IP literals on purpose. A literal skips the DNS lookup, which keeps
 * every case in this file deterministic and offline.
 */
class SourceFetcherTest extends TestCase
{
    use RefreshDatabase;

    private const PAGE = <<<'HTML'
        <html><head>
            <title>Loop engineering · Addy Osmani</title>
            <script>var tracking = 'should never be taught';</script>
            <style>body { color: red }</style>
        </head><body>
            <nav>Home Blog About</nav>
            <article>
                <h1>Loop engineering</h1>
                <p>Loop engineering designs the whole cycle an agent runs in, not one prompt.</p>
                <p>The stop condition matters more than the phrasing of the request. PLACEHOLDER</p>
            </article>
            <footer>Copyright notice nobody needs to learn</footer>
        </body></html>
        HTML;

    public static function refusedUrls(): array
    {
        return [
            'loopback' => ['http://127.0.0.1/admin'],
            'private class A' => ['http://10.1.2.3/internal'],
            'private class C' => ['http://192.168.0.5/router'],
            'link-local metadata' => ['http://169.254.169.254/latest/meta-data/'],
            'ipv6 loopback' => ['http://[::1]/admin'],
            'not http' => ['file:///etc/passwd'],
            'no host' => ['https:///nowhere'],
        ];
    }

    #[DataProvider('refusedUrls')]
    public function test_it_refuses_hosts_that_are_not_publicly_reachable(string $url): void
    {
        Http::fake();

        $result = app(SourceFetcher::class)->fetch($url);

        $this->assertFalse($result->isSuccessfulCheck());
        Http::assertNothingSent();
    }

    public function test_it_extracts_readable_prose_and_drops_the_furniture(): void
    {
        Http::fake(['*' => Http::response($this->page(), 200, ['Content-Type' => 'text/html; charset=utf-8'])]);

        $result = app(SourceFetcher::class)->fetch('https://93.184.216.34/blog/loop-engineering/');

        $this->assertTrue($result->isSuccessfulCheck());

        $attributes = $result->getValidatedItem('attributes');

        $this->assertSame('Loop engineering · Addy Osmani', $attributes['title']);
        $this->assertSame('93.184.216.34', $attributes['site']);
        $this->assertStringContainsString('designs the whole cycle', $attributes['text']);
        $this->assertStringNotContainsString('tracking', $attributes['text']);
        $this->assertStringNotContainsString('Copyright notice', $attributes['text']);
        $this->assertStringNotContainsString('<', $attributes['text']);
        $this->assertGreaterThan(100, $attributes['words']);
    }

    public function test_a_page_with_nothing_to_read_is_refused_rather_than_taught(): void
    {
        Http::fake(['*' => Http::response('<html><body><p>Hi.</p></body></html>', 200, ['Content-Type' => 'text/html'])]);

        $result = app(SourceFetcher::class)->fetch('https://93.184.216.34/thin');

        $this->assertFalse($result->isSuccessfulCheck());
    }

    public function test_a_non_html_response_is_refused(): void
    {
        Http::fake(['*' => Http::response('%PDF-1.7', 200, ['Content-Type' => 'application/pdf'])]);

        $this->assertFalse(app(SourceFetcher::class)->fetch('https://93.184.216.34/paper.pdf')->isSuccessfulCheck());
    }

    public function test_a_url_in_the_composer_opens_a_subject_grounded_in_that_page(): void
    {
        $this->app->instance(LlmClient::class, new FakeLlmClient);
        Http::fake(['*' => Http::response($this->page(), 200, ['Content-Type' => 'text/html'])]);

        $user = User::factory()->create();
        $opened = app(DescentService::class)->open($user, 'https://93.184.216.34/blog/loop-engineering/');

        $this->assertTrue($opened->isSuccessfulCheck());

        $subject = $opened->getValidatedItem('conversation');

        // No framing of their own, so the page's title names the subject.
        $this->assertSame('Loop engineering · Addy Osmani', $subject->subject);
        $this->assertCount(1, $subject->sources);
        $this->assertStringContainsString('stop condition', $subject->sources->first()->text);
    }

    public function test_words_typed_beside_the_link_win_as_the_subject(): void
    {
        $this->app->instance(LlmClient::class, new FakeLlmClient);
        Http::fake(['*' => Http::response($this->page(), 200, ['Content-Type' => 'text/html'])]);

        $opened = app(DescentService::class)->open(
            User::factory()->create(),
            'https://93.184.216.34/blog/loop-engineering/ — how stop conditions work',
        );

        $this->assertSame('how stop conditions work', $opened->getValidatedItem('conversation')->subject);
    }

    public function test_a_failed_fetch_does_not_open_a_subject(): void
    {
        $this->app->instance(LlmClient::class, new FakeLlmClient);
        Http::fake(['*' => Http::response('nope', 500)]);

        $opened = app(DescentService::class)->open(User::factory()->create(), 'https://93.184.216.34/gone');

        $this->assertFalse($opened->isSuccessfulCheck());
        $this->assertDatabaseCount('conversations', 0);
    }

    /** Padded past the 100-word floor a page needs to be worth descending through. */
    private function page(): string
    {
        return str_replace('PLACEHOLDER', str_repeat('A loop needs a signal that says it is finished. ', 30), self::PAGE);
    }
}
