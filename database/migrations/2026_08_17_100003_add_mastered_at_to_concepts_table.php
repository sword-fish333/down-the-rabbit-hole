<?php

use App\Models\Concept;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * When this concept was first proven mastered — set once, never cleared.
     *
     * `state` answers "where does this concept stand now", which is what the
     * mastery map needs and what the guide resurfaces from. It cannot answer
     * "has this learner ever mastered it", because a concept fumbled later
     * moves back to misunderstood. Rankings and the learning record need the
     * second question: an achievement that a bad day cannot take away, and one
     * that can be counted inside a time window.
     */
    public function up(): void
    {
        Schema::table('concepts', function (Blueprint $table) {
            $table->timestamp('mastered_at')->nullable()->after('reviewed_at');
        });

        // Everything already mastered was mastered at its last graded evidence.
        DB::table('concepts')
            ->where('state', Concept::STATE_MASTERED)
            ->update(['mastered_at' => DB::raw('coalesce(reviewed_at, updated_at)')]);
    }

    public function down(): void
    {
        Schema::table('concepts', function (Blueprint $table) {
            $table->dropColumn('mastered_at');
        });
    }
};
