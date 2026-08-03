<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * How a learner organises their own subjects — an adjacency-list tree, the
     * same shape a file explorer uses. Depth is capped in
     * App\Services\Chat\SubjectFolderService, not here: the constraint is a
     * product decision, and the schema shouldn't pretend otherwise.
     */
    public function up(): void
    {
        Schema::create('subject_folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Deleting a folder takes its subfolders with it; the subjects inside
            // survive and fall back to unfiled (see conversations.folder_id).
            $table->foreignId('parent_id')->nullable()->constrained('subject_folders')->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();

            $table->index(['user_id', 'parent_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_folders');
    }
};
