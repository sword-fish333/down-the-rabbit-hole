<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->foreignId('learning_mode_id')->nullable()->after('user_id')
                ->constrained()->nullOnDelete();
            // Running summary of the turns that fell out of the verbatim window.
            $table->text('summary')->nullable()->after('status');
            $table->timestamp('surfaced_at')->nullable()->after('message_count');
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('learning_mode_id');
            $table->dropColumn(['summary', 'surfaced_at']);
        });
    }
};
