<?php

use App\Models\Conversation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            // Null = an anonymous guest hole, claimed when the visitor signs up.
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('subject');
            $table->string('title')->nullable();
            $table->unsignedTinyInteger('current_depth')->default(0);
            $table->enum('status', Conversation::STATUSES)->default(Conversation::STATUS_EXPLORING);
            $table->unsignedInteger('message_count')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
