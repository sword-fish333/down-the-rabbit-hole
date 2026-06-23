<?php

namespace App\Listeners;

use App\Events\LayerCompleted;
use App\Models\XpEvent;
use App\Services\Gamification\StreakService;
use App\Services\Gamification\XpService;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Turns a completed layer into rewards: XP into the ledger, and a daily-streak
 * advance. Queued — the learner's stream never waits on it.
 */
class AwardLayerRewards implements ShouldQueue
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
            (int) config('platform.chat.layer_xp'),
            $event->conversation,
            $event->depth,
        );

        $this->streaks->advance($event->user);
    }
}
