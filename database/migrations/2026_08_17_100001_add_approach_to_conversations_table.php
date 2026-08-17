<?php

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * How a layer opens: taught first, or asked first.
     *
     * The approach lives on the subject because it is a property of *this*
     * descent — switching it mid-subject must not rewrite the layers already
     * behind the learner. `users.preferred_approach` is only the composer's
     * opening bid: the last choice, remembered, so a learner who prefers being
     * asked cold never has to say so twice.
     *
     * `messages.phase` gains `question` so a posed-but-untaught layer is
     * distinguishable from a taught one — that difference is what decides
     * whether the next turn teaches or asks.
     */
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->enum('approach', Conversation::APPROACHES)
                ->default(Conversation::APPROACH_GUIDED)
                ->after('learning_mode_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('preferred_approach', 12)->nullable()->after('locale');
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->enum('phase', Message::PHASES)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn('approach');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('preferred_approach');
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->enum('phase', [Message::PHASE_TEACH, Message::PHASE_GRADE])->nullable()->change();
        });
    }
};
