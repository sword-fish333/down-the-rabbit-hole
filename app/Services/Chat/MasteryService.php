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
 * A concept never regresses out of `mastered`: proving something and later
 * fumbling it in passing shouldn't erase the proof.
 */
class MasteryService
{
    /**
     * Fold one graded checkpoint into the map.
     */
    public function record(Conversation $conversation, GradingResult $result, int $depth): void
    {
        foreach ($result->demonstratedConcepts as $name) {
            $this->demonstrated($conversation, $name, $depth);
        }

        foreach ($result->missingConcepts as $name) {
            $this->encountered($conversation, $name, $depth);
        }

        foreach ($result->misconceptions as $misconception) {
            $this->misunderstood($conversation, $misconception, $depth);
        }
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

    private function demonstrated(Conversation $conversation, string $name, int $depth): void
    {
        $concept = $this->upsert($conversation, $name, $depth);

        $concept->increment('demonstrations');
        $concept->refresh();

        $threshold = (int) config('platform.mastery.demonstrations_to_master');

        $concept->update([
            'state' => $concept->demonstrations >= $threshold ? Concept::STATE_MASTERED : Concept::STATE_DEVELOPING,
            'note' => null,
            'reviewed_at' => now(),
        ]);
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
