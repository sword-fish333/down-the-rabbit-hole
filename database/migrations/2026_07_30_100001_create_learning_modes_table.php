<?php

use App\Models\LearningMode;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // How the guide teaches a layer. Content-managed from the admin panel so
        // the pedagogy can be tuned without a deploy.
        Schema::create('learning_modes', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('tagline')->nullable();
            $table->text('description')->nullable();
            // Appended to the per-turn teaching directive — the whole point of a mode.
            $table->text('prompt_directive');
            $table->string('icon')->default('school');
            $table->enum('accent', LearningMode::ACCENTS)->default(LearningMode::ACCENT_WAVE);
            $table->unsignedSmallInteger('position')->default(0);
            $table->boolean('enabled')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['enabled', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_modes');
    }
};
