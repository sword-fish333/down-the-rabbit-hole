<?php

namespace App\Services\Learning;

use App\Contracts\LlmClient;
use App\DTOs\Llm\TeachingRequest;
use App\Models\MethodNote;
use Throwable;

/**
 * The reference desk: one written entry per learning method, per language.
 *
 * The split here is the point. Which methods exist, what grounds each one in
 * this product, and — above all — what to read next are hand-written and live
 * in config('platform.methods'). Only the prose comes from a model, and it is
 * written once per (method, language) and stored, so the page is a document
 * rather than a fresh answer every time somebody opens it.
 *
 * A model is never the source of a citation. It will produce a plausible
 * author, year and DOI for a paper that does not exist, on a page whose entire
 * claim is that you can go and check — so the prompt forbids citing anything,
 * and the reading list is rendered from config beside it.
 *
 * No fourth verb on {@see LlmClient}: an entry is prose from a prompt, which is
 * exactly what streamTeachingTurn already is. It is drained rather than
 * streamed, because nobody watches a reference page type itself.
 */
class MethodLibrary
{
    public function __construct(private readonly LlmClient $llm) {}

    /** @return array<string, array<string, mixed>> */
    public function all(): array
    {
        return config('platform.methods', []);
    }

    /** @return array<string, mixed>|null */
    public function find(string $slug): ?array
    {
        return $this->all()[$slug] ?? null;
    }

    /**
     * The written entry, in this language. Null when the method is unknown or
     * the model was unreachable — the page still renders its title, its summary
     * and its reading list, because none of those came from a model.
     */
    public function entry(string $slug, string $locale): ?string
    {
        $method = $this->find($slug);

        if ($method === null) {
            return null;
        }

        $stored = MethodNote::query()->where('slug', $slug)->where('locale', $locale)->first();

        if ($stored !== null) {
            return $stored->body;
        }

        try {
            $stream = $this->llm->streamTeachingTurn($this->request($slug, $method, $locale));

            // Drained, not forwarded: this is a page, not a turn.
            foreach ($stream as $ignored) {
                // no-op
            }

            $body = trim($stream->text());
        } catch (Throwable $exception) {
            fullLog($exception);

            return null;
        }

        if ($body === '') {
            return null;
        }

        // ponytail: no lock. Two simultaneous first visitors cost one extra
        // model call and updateOrCreate makes the race harmless; a lock only
        // earns its keep if these pages ever get real concurrent traffic while
        // still cold, which is a state that lasts one request per language.
        MethodNote::updateOrCreate(
            ['slug' => $slug, 'locale' => $locale],
            ['body' => $body, 'model' => $stream->model],
        );

        return $body;
    }

    /**
     * @param  array<string, mixed>  $method
     */
    private function request(string $slug, array $method, string $locale): TeachingRequest
    {
        $models = config('platform.chat.models.'.config('platform.chat.provider'));

        return new TeachingRequest(
            // Mid, not cheap: this is written once and then read by everyone
            // who ever opens the page, which is the best value-for-tokens the
            // product has. Not deep either — it is a summary of settled work.
            model: $models['mid'],
            system: $this->system(),
            messages: [['role' => 'user', 'content' => $this->brief($slug, $method, $locale)]],
            depth: 0,
            maxTokens: (int) config('platform.chat.max_tokens'),
        );
    }

    private function system(): string
    {
        return <<<'PROMPT'
        You write the reference entries for "Down the Rabbit Hole" — a deep-learning app where a
        learner names a subject and descends through it one layer at a time, proving they understood
        each layer before the next, deeper one opens.

        These entries explain the learning techniques the product is built on. They are read by adults
        deciding whether a technique is worth their time, so the register is academic and plain:
        no marketing, no hype, no encouragement, no second person pep talk, no exclamation marks.

        RULES
        - Never invent a citation, an author, a year, a percentage or an effect size. Do not produce a
          reading list at all — the page already carries a hand-checked one, rendered underneath you.
          Where you must refer to a body of work, do it in words ("replicated across many studies"),
          never with a fabricated reference.
        - Be honest about the limits. Where a technique is popular but weakly supported, where it works
          only for some material, or where the research is contested, say so in the same tone as the
          rest. An entry that oversells is worse than no entry.
        - Markdown only: `##` for section headings, short paragraphs, a list only where a list is
          genuinely clearer. No title — the page already prints one. No closing summary, no call to
          action, nothing after the last section.
        PROMPT;
    }

    /**
     * @param  array<string, mixed>  $method
     */
    private function brief(string $slug, array $method, string $locale): string
    {
        $language = config("platform.locales.{$locale}.prompt", 'English');
        $name = __("frontend.methods.items.{$slug}.name", [], $locale);
        $summary = __("frontend.methods.items.{$slug}.summary", [], $locale);
        $glossary = __('frontend.methods.glossary', [], $locale);

        return <<<PROMPT
        [LANGUAGE] Write the entire entry in {$language}, in the register an educated native speaker
        would actually write. Keep the established term in the form the field uses and gloss it once,
        in {$language}, the first time it appears — but leave no other English word untranslated.

        [GLOSSARY] This product's own vocabulary in {$language}. Where the entry refers to any of
        these, use exactly this word and never a synonym or a coinage of your own:
        {$glossary}

        Write the reference entry for: {$name}.
        The page already introduces it with this one-line summary, so do not repeat it: "{$summary}"

        Five sections, in this order, with headings written in {$language}:
        1. What it is — the mechanism itself, concretely enough that a reader could do it tomorrow.
        2. Why it works — what is actually happening in memory or attention.
        3. How to use it well — including the mistake people usually make with it.
        4. What the evidence supports, and what it does not — be specific about the limits.
        5. Where you meet it in this app — write this section strictly from the note below. Do not
           invent product behaviour, and keep the note's honesty about what is only partly done.

        [IN THIS APP] {$method['here']}

        Around 400 words in total. Nothing before the first heading.
        PROMPT;
    }
}
