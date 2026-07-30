<?php

namespace App\Http\Controllers\FrontEnd;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\LearningMode;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        return view('frontend.home.index', [
            'modes' => LearningMode::query()->enabled()->ordered()->get(),
            // Signed-in learners see "resume" ahead of "start" — returning to an
            // unfinished hole is the behaviour worth optimising for.
            'resumable' => auth()->check()
                ? Conversation::query()
                    ->ownedBy(auth()->id())
                    ->where('status', '!=', Conversation::STATUS_SURFACED)
                    ->latest('updated_at')
                    ->first()
                : null,
        ]);
    }
}
