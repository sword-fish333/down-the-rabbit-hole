<?php

namespace App\Listeners;

use App\Events\LayerCompleted;
use App\Models\XpEvent;
use App\Services\Gamification\StreakService;
use App\Services\Gamification\XpService;

/**
 * Turns a completed layer into rewards: XP into the ledger, and a daily-streak
 * advance.
 *
 * What each entry is worth lives in `platform.rewards`; what each entry *means*
 * lives here. Three distinct behaviours are paid for, because a single flat
 * award per layer would rank volume above depth and above reading properly.
 *
 * Deliberately NOT queued. It fires while grading a checkpoint — a request that
 * has already waited on a model round-trip, so four inserts are not the cost
 * anyone notices — and the ledger is the sole evidence behind XP, the record and
 * every ranking. Off a queue, a stopped worker is a learner whose proven layers
 * silently stopped counting, which is the one failure this app cannot have.
 */
class AwardLayerRewards
{
    public function __construct(
        private readonly XpService $xp,
        private readonly StreakService $streaks,
    ) {}

    public function handle(LayerCompleted $event): void
    {
        $this->xp->award(
            $event->user,
            XpEvent::TYPE_LAYER_COMPLETED,
            $this->layerAmount($event->depth),
            $event->conversation,
            $event->depth,
        );

        // Cleared without a failed attempt: paid separately so it shows in the
        // ledger as its own thing rather than as a bigger, unexplained number.
        if ($event->firstTry) {
            $this->xp->award(
                $event->user,
                XpEvent::TYPE_FIRST_TRY,
                (int) config('platform.rewards.first_try_xp'),
                $event->conversation,
                $event->depth,
            );
        }

        if ($event->surfaced) {
            $this->xp->award(
                $event->user,
                XpEvent::TYPE_SUBJECT_SURFACED,
                (int) config('platform.rewards.surfaced_xp'),
                $event->conversation,
                $event->depth,
            );
        }

        $this->streaks->advance($event->user);
    }

    /** Deeper layers are harder, so they are worth more. Layer 00 is the floor. */
    private function layerAmount(int $depth): int
    {
        return (int) config('platform.rewards.layer_xp')
            + max(0, $depth) * (int) config('platform.rewards.layer_depth_xp');
    }
}
