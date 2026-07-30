<?php

namespace App\DTOs\Llm;

use Closure;
use Generator;
use IteratorAggregate;
use Traversable;

/**
 * A lazily-consumed stream of text deltas plus the usage totals that only exist
 * once the stream closes. Iterate it to forward deltas to the browser, then read
 * {@see text()} / {@see usage()} for the assembled turn.
 *
 * Partial results are intentional: if the learner disconnects mid-turn the
 * generator stops early, and the caller still gets everything received so far.
 *
 * @implements IteratorAggregate<int, string>
 */
final class LlmStream implements IteratorAggregate
{
    private string $text = '';

    private LlmUsage $usage;

    private bool $finished = false;

    /**
     * @param  Closure(): Generator<int, string>  $frames  yields text deltas
     * @param  Closure(): LlmUsage  $usageResolver  called once the generator is exhausted
     */
    public function __construct(
        private readonly Closure $frames,
        private readonly Closure $usageResolver,
        public readonly string $model = '',
    ) {
        $this->usage = new LlmUsage;
    }

    /**
     * @return Traversable<int, string>
     */
    public function getIterator(): Traversable
    {
        foreach (($this->frames)() as $delta) {
            if ($delta === '') {
                continue;
            }

            $this->text .= $delta;

            yield $delta;
        }

        $this->usage = ($this->usageResolver)();
        $this->finished = true;
    }

    /** Everything received so far — the full turn once the stream has closed. */
    public function text(): string
    {
        return $this->text;
    }

    public function usage(): LlmUsage
    {
        return $this->usage;
    }

    /** False when the learner disconnected before the provider was done. */
    public function isComplete(): bool
    {
        return $this->finished;
    }
}
