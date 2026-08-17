<?php

namespace App\Services\Gamification;

use App\Models\Concept;
use App\Models\Conversation;
use App\Models\User;
use App\Models\XpEvent;

/**
 * What a learner has actually earned — the numbers behind the profile, the
 * public record and the boards.
 *
 * It exists so those three can never disagree. Every achievement number comes
 * from the `xp_events` ledger, which is the same source the rankings aggregate,
 * so "12 layers cleared" on a profile is the same twelve rows that put someone
 * twelfth on the layers board. Only the two genuinely *live* questions — how
 * many subjects exist, and what still needs revisiting — are read from current
 * state, because no ledger can answer them.
 */
class LearningRecordService
{
    /**
     * @return array{xp: int, subjects: int, surfaced: int, deepest: int, layers: int, mastered: int, to_review: int}
     */
    public function for(User $user): array
    {
        return $this->earned($user) + [
            'xp' => (int) $user->xp,
            'subjects' => $user->conversations()->count(),
            'to_review' => Concept::query()
                ->whereIn('conversation_id', $user->conversations()->select('id'))
                ->needingReview()
                ->count(),
        ];
    }

    /**
     * The subject they got furthest into, and the one line worth quoting out
     * loud: "7 layers deep on Stoicism".
     */
    public function deepestDive(User $user): ?Conversation
    {
        return $user->conversations()->deepestFirst()->latest('updated_at')->first();
    }

    /**
     * Everything the ledger knows about this learner, in one pass. Four
     * aggregates over one indexed range beats four round trips, and it keeps
     * the definitions of "a layer cleared" and "a concept mastered" in a single
     * place next to each other.
     *
     * `deepest` is max cleared depth + 1 because a ledger row records the layer
     * that was cleared and layers are cleared in order — so it reads as the
     * number of layers proven in the deepest single subject.
     *
     * @return array{layers: int, mastered: int, surfaced: int, deepest: int}
     */
    private function earned(User $user): array
    {
        $counted = 'sum(case when type = ? then 1 else 0 end)';

        $totals = XpEvent::query()
            ->where('user_id', $user->id)
            ->selectRaw("{$counted} as layers", [XpEvent::TYPE_LAYER_COMPLETED])
            ->selectRaw("{$counted} as mastered", [XpEvent::TYPE_CONCEPT_MASTERED])
            ->selectRaw("{$counted} as surfaced", [XpEvent::TYPE_SUBJECT_SURFACED])
            ->selectRaw('max(case when type = ? then depth end) as deepest_cleared', [XpEvent::TYPE_LAYER_COMPLETED])
            ->first();

        return [
            'layers' => (int) $totals?->layers,
            'mastered' => (int) $totals?->mastered,
            'surfaced' => (int) $totals?->surfaced,
            'deepest' => $totals?->deepest_cleared === null ? 0 : (int) $totals->deepest_cleared + 1,
        ];
    }
}
