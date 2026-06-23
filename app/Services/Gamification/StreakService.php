<?php

namespace App\Services\Gamification;

use App\Models\Streak;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Advances the user's "White Rabbit" daily streak. Idempotent within a day, so
 * completing several layers in one day counts once.
 */
class StreakService
{
    public function advance(User $user): Streak
    {
        $streak = $user->streak()->firstOrCreate([]);
        $today = Carbon::today();

        if ($streak->last_activity_date?->isSameDay($today)) {
            return $streak;
        }

        $continued = $streak->last_activity_date?->isSameDay($today->copy()->subDay());
        $current = $continued ? $streak->current_count + 1 : 1;

        $streak->update([
            'current_count' => $current,
            'longest_count' => max($current, $streak->longest_count),
            'last_activity_date' => $today,
        ]);

        return $streak;
    }
}
