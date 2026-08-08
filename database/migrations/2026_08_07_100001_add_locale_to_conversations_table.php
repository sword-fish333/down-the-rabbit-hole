<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The language the guide teaches this subject in.
     *
     * Stored per subject rather than read from the request, because the two
     * genuinely differ: a learner can switch the interface to English and still
     * be mid-descent through a Romanian subject, and a descent that changes
     * language at layer 4 is a broken descent.
     */
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->string('locale', 12)->default('en')->after('subject');
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn('locale');
        });
    }
};
