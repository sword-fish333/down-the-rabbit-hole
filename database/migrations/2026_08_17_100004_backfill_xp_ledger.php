<?php

use App\Models\Conversation;
use App\Models\XpEvent;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The ledger gained three event types (mastered concept, first-try clear,
     * completed subject). Learners who earned those before the types existed
     * would otherwise read as having earned nothing, and every windowed ranking
     * is computed from this table — so the history is written in.
     *
     * Only the two types recoverable from state are backfilled: a first-try
     * clear cannot be reconstructed after the fact (the attempts that led to a
     * pass are not linked to the layer that opened), and inventing one would put
     * a number on the boards that nothing actually evidences.
     *
     * `users.xp` is then recomputed from the ledger rather than incremented —
     * the column is a projection, so rebuilding it is the honest repair.
     */
    public function up(): void
    {
        $this->backfillMasteredConcepts();
        $this->backfillSurfacedSubjects();
        $this->recomputeBalances();
    }

    public function down(): void
    {
        XpEvent::query()
            ->whereIn('type', [XpEvent::TYPE_CONCEPT_MASTERED, XpEvent::TYPE_SUBJECT_SURFACED])
            ->delete();

        $this->recomputeBalances();
    }

    /** One event per concept the learner has already proven twice over. */
    private function backfillMasteredConcepts(): void
    {
        $amount = (int) config('platform.rewards.concept_xp');

        DB::table('concepts')
            ->join('conversations', 'conversations.id', '=', 'concepts.conversation_id')
            ->whereNotNull('conversations.user_id')
            ->whereNotNull('concepts.mastered_at')
            ->select([
                'conversations.user_id',
                'concepts.conversation_id',
                'concepts.last_seen_depth',
                'concepts.mastered_at',
            ])
            ->orderBy('concepts.id')
            ->chunk(500, function ($concepts) use ($amount) {
                XpEvent::insert($concepts->map(fn ($concept) => [
                    'user_id' => $concept->user_id,
                    'conversation_id' => $concept->conversation_id,
                    'type' => XpEvent::TYPE_CONCEPT_MASTERED,
                    'amount' => $amount,
                    'depth' => $concept->last_seen_depth,
                    'created_at' => $concept->mastered_at,
                    'updated_at' => $concept->mastered_at,
                ])->all());
            });
    }

    /** One event per descent already carried all the way to the bottom. */
    private function backfillSurfacedSubjects(): void
    {
        $amount = (int) config('platform.rewards.surfaced_xp');

        DB::table('conversations')
            ->whereNotNull('user_id')
            ->where('status', Conversation::STATUS_SURFACED)
            ->select(['id', 'user_id', 'current_depth', 'surfaced_at', 'updated_at'])
            ->orderBy('id')
            ->chunk(500, function ($subjects) use ($amount) {
                XpEvent::insert($subjects->map(fn ($subject) => [
                    'user_id' => $subject->user_id,
                    'conversation_id' => $subject->id,
                    'type' => XpEvent::TYPE_SUBJECT_SURFACED,
                    'amount' => $amount,
                    'depth' => $subject->current_depth,
                    'created_at' => $subject->surfaced_at ?? $subject->updated_at,
                    'updated_at' => $subject->surfaced_at ?? $subject->updated_at,
                ])->all());
            });
    }

    /** users.xp is a cached sum of the ledger — rebuild it from the ledger. */
    private function recomputeBalances(): void
    {
        DB::table('users')->update([
            'xp' => DB::raw('(select coalesce(sum(amount), 0) from xp_events where xp_events.user_id = users.id)'),
        ]);
    }
};
