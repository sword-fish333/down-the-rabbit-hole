<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // `phase` was `enum('phase', Message::PHASES)`, which reads like it
        // follows the constant and does not: the enum was frozen into the
        // deployed schema at migration time, so adding PHASE_SURVEY to the class
        // left MySQL rejecting the value with "Data truncated for column
        // 'phase'". SQLite is lenient enough that the whole suite stayed green.
        //
        // A free string instead, for the same reason `xp_events.type` is one:
        // the value is only ever written from a class constant, never from
        // input, so the enum bought no safety and charged a migration per phase.
        Schema::table('messages', function (Blueprint $table) {
            $table->string('phase', 20)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->enum('phase', ['teach', 'question', 'grade'])->nullable()->change();
        });
    }
};
