<?php

namespace App\Http\Controllers\FrontEnd;

use App\Http\Controllers\Controller;
use App\Services\Gamification\RankingService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The boards. Six measures, three windows, one table.
 *
 * Which board and which window are in the URL rather than in a cookie or a
 * session: a ranking is the one page in this app people send each other, and a
 * link that lands on someone else's board is a broken link.
 */
class RankingController extends Controller
{
    public function __construct(private readonly RankingService $rankings) {}

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'board' => ['nullable', Rule::in(RankingService::BOARDS)],
            'period' => ['nullable', Rule::in(RankingService::PERIODS)],
        ]);

        $board = $validated['board'] ?? RankingService::BOARD_XP;
        $period = $validated['period'] ?? RankingService::PERIOD_ALL;
        $user = $request->user();

        return view('frontend.rankings.index', [
            'board' => $board,
            'period' => $period,
            'rows' => $this->rankings->board($board, $period),
            // Their own line, always — top ten or nowhere near it.
            'standing' => $this->rankings->standing($user, $board, $period),
            'participants' => $this->rankings->participants(),
        ]);
    }
}
