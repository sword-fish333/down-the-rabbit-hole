<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Every board is `where type = ? [and created_at >= ?] group by user_id`
        // over the whole ledger. The table only carried (user_id, created_at),
        // which serves one learner's own standing and does nothing at all for
        // the six aggregates the rankings page is made of — those were full
        // scans, and xp_events is the one table that grows with every layer
        // anyone anywhere clears.
        Schema::table('xp_events', function (Blueprint $table) {
            $table->index(['type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('xp_events', function (Blueprint $table) {
            $table->dropIndex(['type', 'created_at']);
        });
    }
};
