<?php

namespace App\Services\Chat;

use App\Services\ValidationService;
use App\Traits\ValidationHelper;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Psr\Http\Message\UriInterface;
use RuntimeException;
use Throwable;

/**
 * Fetches a web page and reduces it to readable prose, so the guide can teach
 * from the material instead of from a subject name.
 *
 * SSRF is the first thing this has to get right: the URL comes from a visitor
 * and the fetch happens from inside the network. Every host — the original AND
 * every redirect hop — is resolved and checked against private and reserved
 * ranges before a request is allowed to reach it. The bounds (timeout, byte cap,
 * redirect cap) are the second line: a hostile page should be able to waste at
 * most a few seconds and a couple of megabytes.
 *
 * Extraction is intentionally regex-based rather than a DOM library. It only has
 * to be *good enough to teach from* — the guide reads prose, not structure — and
 * that is not worth a dependency on the critical path.
 */
class SourceFetcher
{
    use ValidationHelper;

    /** Wrappers around the body we never want in the extract. */
    private const NOISE = '/<(script|style|noscript|svg|iframe|template|form|nav|footer|aside)\b[^>]*>.*?<\/\1>/is';

    /** Where an article usually starts, in order of how much we trust it. */
    private const CONTENT = ['/<article\b[^>]*>(.*?)<\/article>/is', '/<main\b[^>]*>(.*?)<\/main>/is'];

    public function __construct()
    {
        $this->initializeValidator();
    }

    /**
     * @return ValidationService `attributes` — ready for Source::create(), minus
     *                           the conversation id.
     */
    public function fetch(string $url): ValidationService
    {
        $url = trim($url);

        if (! $this->isPubliclyReachable($url)) {
            return $this->errorEncountered(__('frontend.chat.source-unreachable'));
        }

        try {
            $response = Http::timeout((int) config('platform.sources.timeout'))
                ->withHeaders([
                    // Identify honestly: a page that doesn't want to be read this
                    // way should be able to say so.
                    'User-Agent' => config('app.name').' source reader (+'.config('app.url').')',
                    'Accept' => 'text/html,application/xhtml+xml',
                ])
                ->withOptions(['allow_redirects' => $this->redirectPolicy()])
                ->get($url);
        } catch (Throwable $e) {
            fullLog($e);

            return $this->errorEncountered(__('frontend.chat.source-failed'));
        }

        if ($response->failed() || ! Str::contains((string) $response->header('Content-Type'), ['text/html', 'text/plain', 'xhtml'])) {
            return $this->errorEncountered(__('frontend.chat.source-failed'));
        }

        // A byte cap that can't split a multi-byte character — a truncated
        // sequence would make every /u pattern below fail silently.
        $html = mb_strcut($response->body(), 0, (int) config('platform.sources.max_bytes'), 'UTF-8');
        $text = $this->extractText($html);

        if (Str::wordCount($text) < 100) {
            return $this->errorEncountered(__('frontend.chat.source-too-thin'));
        }

        return $this->addValidatedItems(['attributes' => [
            'url' => Str::limit($url, 2000, ''),
            'title' => $this->extractTitle($html),
            'site' => parse_url($url, PHP_URL_HOST),
            'text' => $text,
            'words' => Str::wordCount($text),
        ]]);
    }

    /**
     * Guzzle re-validates on every hop through this callback, so a public URL
     * cannot redirect the fetch onto localhost or a metadata endpoint.
     *
     * @return array<string, mixed>
     */
    private function redirectPolicy(): array
    {
        return [
            'max' => (int) config('platform.sources.max_redirects'),
            'strict' => true,
            'referer' => false,
            'protocols' => ['http', 'https'],
            'on_redirect' => function (mixed $request, mixed $response, UriInterface $uri): void {
                if (! $this->isPubliclyReachable((string) $uri)) {
                    throw new RuntimeException('Redirect to a non-public host was refused.');
                }
            },
        ];
    }

    /**
     * An http(s) URL whose host resolves only to addresses outside the private
     * and reserved ranges. A host that resolves to nothing is refused too —
     * "unknown" is not the same as "safe".
     */
    private function isPubliclyReachable(string $url): bool
    {
        $parts = parse_url($url);

        if (! in_array($parts['scheme'] ?? '', ['http', 'https'], true) || empty($parts['host'])) {
            return false;
        }

        $addresses = $this->resolve($parts['host']);

        if ($addresses === []) {
            return false;
        }

        foreach ($addresses as $address) {
            if (! filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<int, string>
     */
    private function resolve(string $host): array
    {
        // A literal address needs no lookup — and must not get one, or a
        // hostile "host" could be resolved differently than it is connected to.
        if (filter_var(trim($host, '[]'), FILTER_VALIDATE_IP)) {
            return [trim($host, '[]')];
        }

        $records = @dns_get_record($host, DNS_A + DNS_AAAA) ?: [];

        return array_values(array_filter(array_map(
            fn (array $record) => $record['ip'] ?? $record['ipv6'] ?? null,
            $records,
        )));
    }

    /** The `<title>`, trimmed of the site suffix publishers append to it. */
    private function extractTitle(string $html): ?string
    {
        if (! preg_match('/<title\b[^>]*>(.*?)<\/title>/is', $html, $match)) {
            return null;
        }

        $title = Str::of($this->decode(strip_tags($match[1])))->squish();

        return $title->isEmpty() ? null : $title->limit(200)->value();
    }

    /**
     * Prose only: drop the wrappers, prefer the article body when the page marks
     * one, then flatten to text with paragraph breaks preserved.
     */
    private function extractText(string $html): string
    {
        $html = preg_replace('/<!--.*?-->/s', ' ', $html) ?? $html;
        $html = preg_replace(self::NOISE, ' ', $html) ?? $html;

        foreach (self::CONTENT as $pattern) {
            if (preg_match($pattern, $html, $match) && strlen($match[1]) > 500) {
                $html = $match[1];
                break;
            }
        }

        // Block ends become blank lines so headings and paragraphs survive the
        // flattening — the guide reads structure it can see.
        $html = preg_replace('/<\/(p|div|section|h[1-6]|li|tr|blockquote|pre)>/i', "\n\n", $html) ?? $html;
        $html = preg_replace('/<(br|hr)\b[^>]*\/?>/i', "\n", $html) ?? $html;

        $text = $this->decode(strip_tags($html));
        $text = preg_replace('/[ \t\x{00A0}]+/u', ' ', $text) ?? $text;
        $text = preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;

        return trim(implode("\n", array_map('trim', explode("\n", $text))));
    }

    private function decode(string $text): string
    {
        return html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
