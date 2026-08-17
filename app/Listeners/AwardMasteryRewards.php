<?php

namespace App\Listeners;

use App\Events\ConceptsMastered;
use App\Models\Concept;
use App\Models\XpEvent;
use App\Services\Gamification\XpService;

/**
 * One ledger entry per concept crossing into mastery, so "concepts mastered"
 * over any window is a count of rows rather than a re-derivation.
 *
 * Synchronous for the same reason as AwardLayerRewards: the ledger is evidence,
 * and evidence that depends on a worker being alive is evidence that quietly
 * goes missing.
 */
class AwardMasteryRewards
{
    public function __construct(private readonly XpService $xp) {}

    public function handle(ConceptsMastered $event): void
    {
        $amount = (int) config('platform.rewards.concept_xp');

        $event->concepts->each(fn (Concept $concept) => $this->xp->award(
            $event->user,
            XpEvent::TYPE_CONCEPT_MASTERED,
            $amount,
            $event->conversation,
            $concept->last_seen_depth,
        ));
    }
}
