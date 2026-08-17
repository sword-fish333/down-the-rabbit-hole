<?php

namespace App\Http\Controllers\FrontEnd;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Gamification\LearningRecordService;
use App\Services\Gamification\RankingService;
use Illuminate\View\View;

/**
 * One learner, as other learners see them: what they have proven, where they
 * stand, and the descents they chose to publish.
 *
 * Visible only for learners who joined the boards — that is the same yes, and
 * there is no second setting to get wrong. A learner who has not joined can
 * still open their own page, which is how you see what joining would publish
 * before you agree to it.
 */
class LearnerController extends Controller
{
    public function __construct(
        private readonly LearningRecordService $records,
        private readonly RankingService $rankings,
    ) {}

    public function show(User $user): View
    {
        $isSelf = $user->is(auth()->user());

        // 404 rather than 403: whether an account exists is not public either.
        abort_unless($user->isRanked() || $isSelf, 404);

        return view('frontend.learners.show', [
            'learner' => $user,
            'isSelf' => $isSelf,
            'streak' => $user->streak,
            'record' => $this->records->for($user),
            'deepestDive' => $this->records->deepestDive($user),
            'standings' => $this->rankings->standings($user),
            // Already public by their own hand — nothing new is exposed here.
            'shared' => $user->conversations()
                ->whereNotNull('share_token')
                ->deepestFirst()
                ->latest('shared_at')
                ->limit(6)
                ->get(),
        ]);
    }
}
