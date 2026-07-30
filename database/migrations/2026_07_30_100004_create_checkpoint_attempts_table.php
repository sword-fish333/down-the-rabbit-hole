<?php

use App\Models\CheckpointAttempt;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One graded proof-of-understanding. The verdict comes back as a strict
        // JSON-schema structured output, so every field here is trustworthy.
        Schema::create('checkpoint_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('message_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('depth')->default(0);
            $table->enum('verdict', CheckpointAttempt::VERDICTS);
            $table->unsignedTinyInteger('score')->default(0);         // 0..100
            $table->unsignedTinyInteger('confidence')->default(0);    // grader's, 0..100
            $table->unsignedTinyInteger('self_rating')->nullable();   // learner's, 0..100
            $table->json('criteria')->nullable();
            $table->json('demonstrated_concepts')->nullable();
            $table->json('missing_concepts')->nullable();
            $table->json('misconceptions')->nullable();
            $table->text('feedback')->nullable();
            $table->enum('recommended_action', CheckpointAttempt::ACTIONS)
                ->default(CheckpointAttempt::ACTION_RETRY);
            $table->string('model')->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'depth']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkpoint_attempts');
    }
};
