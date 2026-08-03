<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\CheckpointAttempt;
use App\Models\Concept;
use App\Models\Conversation;
use App\Models\LearningMode;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard', [
            'admin' => auth('admin')->user(),
            'stats' => $this->stats(),
            'learning' => $this->learningStats(),
            'modes' => LearningMode::query()->withCount('conversations')->ordered()->get(),
            'recent' => Conversation::with('user:id,name')->latest('updated_at')->limit(6)->get(),
        ]);
    }

    /**
     * @return array<string, int>
     */
    private function stats(): array
    {
        return [
            'users' => User::count(),
            'subjects' => Conversation::count(),
            'admins' => Admin::count(),
            'active_admins' => Admin::where('enabled', true)->count(),
        ];
    }

    /**
     * The learning-health numbers. Deliberately not "messages sent" or
     * "time on site" — both can rise while learning quality falls.
     *
     * @return array<string, int|float>
     */
    private function learningStats(): array
    {
        $attempts = CheckpointAttempt::count();
        $passes = CheckpointAttempt::where('verdict', CheckpointAttempt::VERDICT_PASS)->count();
        $subjects = Conversation::count();
        $pastFirstLayer = Conversation::where('current_depth', '>=', 1)->count();

        return [
            'layers_cleared' => $passes,
            'pass_rate' => $attempts > 0 ? (int) round($passes / $attempts * 100) : 0,
            'first_layer_rate' => $subjects > 0 ? (int) round($pastFirstLayer / $subjects * 100) : 0,
            'average_depth' => round((float) Conversation::avg('current_depth'), 1),
            'surfaced' => Conversation::where('status', Conversation::STATUS_SURFACED)->count(),
            'mastered_concepts' => Concept::where('state', Concept::STATE_MASTERED)->count(),
            'open_misconceptions' => Concept::where('state', Concept::STATE_MISUNDERSTOOD)->count(),
        ];
    }
}
