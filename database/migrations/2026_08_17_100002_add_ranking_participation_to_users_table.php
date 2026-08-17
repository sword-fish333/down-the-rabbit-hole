<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Whether this learner stands on the boards.
     *
     * Off by default, and deliberately so: appearing on a ranking publishes a
     * name, a face and a learning record to every other learner. That is a yes
     * someone gives, never one they discover they gave. The same flag gates the
     * public profile — joining the boards IS what makes the record public.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('ranked')->default(false)->after('xp');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('ranked');
        });
    }
};
