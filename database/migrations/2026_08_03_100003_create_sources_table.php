<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One web page a subject is grounded in. The extracted text is stored whole
     * and fed to the guide truncated — no chunking, no locators, no retrieval
     * index yet (see docs/PRODUCT.md §7 for the pipeline that layers on top).
     */
    public function up(): void
    {
        Schema::create('sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->string('url', 2048);
            $table->string('title')->nullable();
            $table->string('site')->nullable();
            $table->longText('text');
            $table->unsignedInteger('words')->default(0);
            $table->timestamps();

            $table->index('conversation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sources');
    }
};
