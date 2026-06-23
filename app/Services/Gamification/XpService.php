<?php

namespace App\Services\Gamification;

use App\Models\Conversation;
use App\Models\User;
use App\Models\XpEvent;
use Illuminate\Support\Facades\DB;

/**
 * Awards XP. The xp_events ledger is the source of truth; the users.xp counter
 * is a cached projection. Both are written together, atomically.
 */
class XpService
{
    public function award(User $user, string $type, int $amount, ?Conversation $conversation = null, ?int $depth = null): XpEvent
    {
        return DB::transaction(function () use ($user, $type, $amount, $conversation, $depth) {
            $event = $user->xpEvents()->create([
                'conversation_id' => $conversation?->id,
                'type' => $type,
                'amount' => $amount,
                'depth' => $depth,
            ]);

            $user->increment('xp', $amount);

            return $event;
        });
    }
}
