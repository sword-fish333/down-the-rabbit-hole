<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The subject library: where a subject is filed, and whether it is shared.
     *
     * `share_token` is the capability — its presence IS the share, so revoking
     * is a single null and the old link dies with it.
     */
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->foreignId('folder_id')->nullable()->after('user_id')
                ->constrained('subject_folders')->nullOnDelete();
            $table->string('share_token', 32)->nullable()->unique()->after('status');
            $table->timestamp('shared_at')->nullable()->after('share_token');

            // The library lists by recency inside a folder; the flat list by recency.
            $table->index(['user_id', 'folder_id']);
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'folder_id']);
            $table->dropConstrainedForeignId('folder_id');
            $table->dropColumn(['share_token', 'shared_at']);
        });
    }
};
