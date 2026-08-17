<?php

namespace App\Services\Chat;

use App\DTOs\Llm\GradingResult;
use App\Models\Concept;
use App\Models\Conversation;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Owns the mastery map. Every state change is driven by *graded evidence* — a
 * concept only becomes `mastered` because the learner demonstrated it, and only
 * becomes `misunderstood` because the grader caught a wrong belief. Nothing here
 * is inferred from the learner asserting they understood.
 *
 * Two different questions, two different columns. `state` is where a concept
 * stands *now*, and it does move backwards — a concept fumbled at layer 5 goes
 * back to `misunderstood` so the guide resurfaces it, which is the entire point
 * of the map. `mastered_at` is stamped the first time it was proven and is never
 * cleared, because the learning record and the rankings ask a different
 * question: what has this learner actually earned. A bad day does not unearn it.
 */
class MasteryService
{
    /**
     * Fold one graded checkpoint into the map.
     *
     * Returns the concepts that crossed into mastery on *this* evidence — the
     * caller turns that into a reward. Returned rather than rewarded here so the
     * map stays a map: what a mastery is worth is not this service's business.
     *
     * @return Collection<int, Concept>
     */
    public function record(Conversation $conversation, GradingResult $result, int $depth): Collection
    {
        $mastered = new Collection;

        foreach ($result->demonstratedConcepts as $name) {
            if ($concept = $this->demonstrated($conversation, $name, $depth)) {
                $mastered->push($concept);
            }
        }

        foreach ($result->missingConcepts as $name) {
            $this->encountered($conversation, $name, $depth);
        }

        foreach ($result->misconceptions as $misconception) {
            $this->misunderstood($conversation, $misconception, $depth);
        }

        return $mastered;
    }

    /**
     * Concepts the learner is still wrong about — the guide is asked to weave a
     * correction into the next layer, and the UI marks them "resurfaced".
     *
     * @return Collection<int, Concept>
     */
    public function resurfacing(Conversation $conversation): Collection
    {
        return $conversation->concepts()->needingReview()->orderBy('first_seen_depth')->get();
    }

    /**
     * Counts per state, for the depth rail and the dashboard.
     *
     * @return array<string, int>
     */
    public function tally(Conversation $conversation): array
    {
        $counts = $conversation->concepts()
            ->selectRaw('state, count(*) as total')
            ->groupBy('state')
            ->pluck('total', 'state');

        return collect(Concept::STATES)
            ->mapWithKeys(fn (string $state) => [$state => (int) $counts->get($state, 0)])
            ->all();
    }

    /**
     * @return Concept|null the concept, but only if this is the evidence that
     *                      first carried it over the mastery threshold
     */
    private function demonstrated(Conversation $conversation, string $name, int $depth): ?Concept
    {
        $concept = $this->upsert($conversation, $name, $depth);

        $concept->increment('demonstrations');
        $concept->refresh();

        $threshold = (int) config('platform.mastery.demonstrations_to_master');
        $mastered = $concept->demonstrations >= $threshold;

        // Stamped once and never cleared, so re-proving a concept that regressed
        // cannot be counted — or rewarded — as a second mastery.
        $crossed = $mastered && ! $concept->wasEverMastered();

        $concept->update([
            'state' => $mastered ? Concept::STATE_MASTERED : Concept::STATE_DEVELOPING,
            'note' => null,
            'reviewed_at' => now(),
            'mastered_at' => $crossed ? now() : $concept->mastered_at,
        ]);

        return $crossed ? $concept : null;
    }

    private function encountered(Conversation $conversation, string $name, int $depth): void
    {
        $concept = $this->upsert($conversation, $name, $depth);

        if ($concept->state === Concept::STATE_UNEXPLORED) {
            $concept->update(['state' => Concept::STATE_DEVELOPING]);
        }
    }

    /**
     * @param  array<string, mixed>  $misconception
     */
    private function misunderstood(Conversation $conversation, array $misconception, int $depth): void
    {
        $name = trim((string) ($misconception['concept'] ?? ''));

        if ($name === '') {
            return;
        }

        $concept = $this->upsert($conversation, $name, $depth);

        $concept->increment('misconceptions');
        $concept->update([
            'state' => Concept::STATE_MISUNDERSTOOD,
            'note' => trim((string) ($misconception['belief'] ?? '')) ?: null,
            'reviewed_at' => now(),
        ]);
    }

    /**
     * Find-or-create by slug so "Pure functions" and "pure functions" are one node.
     */
    private function upsert(Conversation $conversation, string $name, int $depth): Concept
    {
        $name = Str::of($name)->trim()->limit(120)->value();
        $slug = Str::slug($name);

        if ($slug === '') {
            $slug = Str::slug(Str::limit($name, 40, '')) ?: md5($name);
        }

        $concept = $conversation->concepts()->firstOrCreate(
            ['slug' => $slug],
            ['name' => $name, 'first_seen_depth' => $depth, 'last_seen_depth' => $depth],
        );

        if ($concept->last_seen_depth !== $depth) {
            $concept->update(['last_seen_depth' => $depth]);
        }

        return $concept;
    }
}
