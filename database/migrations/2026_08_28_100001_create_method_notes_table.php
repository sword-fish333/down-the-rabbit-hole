<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One written entry per method per language. Generated on the first
        // request for that pair and then served from here forever — a reference
        // page that re-asked a model on every view would be slow, expensive and
        // subtly different every time somebody reloaded it.
        //
        // A row is safe to delete: the next visit rewrites it. That is the whole
        // regeneration story, and it is why there is no admin screen for this.
        Schema::create('method_notes', function (Blueprint $table) {
            $table->id();
            // Matches a key of config('platform.methods') — the registry is the
            // authority on which methods exist, not this table.
            $table->string('slug');
            $table->string('locale', 12);
            $table->text('body');
            // Which model wrote it, so a bad batch can be found and dropped.
            $table->string('model')->nullable();
            $table->timestamps();

            $table->unique(['slug', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('method_notes');
    }
};
