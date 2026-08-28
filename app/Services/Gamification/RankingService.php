<?php

namespace App\Services\Gamification;

use App\Models\User;
use App\Models\XpEvent;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * The boards.
 *
 * Every one of them is the same query over the same table — `xp_events`, the
 * append-only ledger — with a different aggregate and a different type filter.
 * That is the whole design, and it is what makes six boards across three time
 * windows cost one query shape instead of eighteen: a ledger row is dated, so
 * "this week" is a `where`, not a second schema.
 *
 * It also fixes what a leaderboard is for. Ranking on live state ("how many
 * concepts are currently marked mastered") rewards whatever the map happens to
 * say today and cannot be windowed at all. Ranking on evidence — the moment a
 * layer was cleared, the moment a concept was first proven — is a record of what
 * someone did, which is the only thing worth competing over.
 *
 * Standing on a board is opt-in (`users.ranked`). A learner who has not opted in
 * is absent from every board but can still be told where they *would* stand,
 * which is a far better invitation than an empty page.
 */
class RankingService
{
    public const string BOARD_XP = 'xp';

    public const string BOARD_LAYERS = 'layers';

    public const string BOARD_CONCEPTS = 'concepts';

    public const string BOARD_DEPTH = 'depth';

    public const string BOARD_SUBJECTS = 'subjects';

    public const string BOARD_SURFACED = 'surfaced';

    /** Ordered as they appear in the rail: broadest measure first. */
    public const array BOARDS = [
        self::BOARD_XP,
        self::BOARD_LAYERS,
        self::BOARD_CONCEPTS,
        self::BOARD_DEPTH,
        self::BOARD_SUBJECTS,
        self::BOARD_SURFACED,
    ];

    public const string PERIOD_ALL = 'all';

    public const string PERIOD_MONTH = 'month';

    public const string PERIOD_WEEK = 'week';

    public const array PERIODS = [self::PERIOD_ALL, self::PERIOD_MONTH, self::PERIOD_WEEK];

    /**
     * Bumped whenever the *population* of the boards changes — someone joins,
     * someone leaves. Every cached board key carries it, so one write
     * invalidates all eighteen of them without needing tags (the file and
     * database cache drivers have none) and without flushing anyone else's
     * cache.
     *
     * Nobody needs a leaderboard to the second, so XP earned inside the window
     * is deliberately NOT bumped here — but "I joined and I am not on it" reads
     * as broken, and waiting five minutes to find out you are there is a bad
     * first minute of the one feature that is opt-in.
     */
    private const string VERSION_KEY = 'dth:ranking:version';

    /**
     * board => [aggregate over xp_events, the event type it counts (null = all)].
     *
     * The expressions are constants, never input — `$board` is checked against
     * BOARDS before it reaches this map.
     *
     * `depth` is `max(depth) + 1` on purpose: a ledger row stores the layer that
     * was *cleared*, and layers within a subject are cleared in order, so the
     * deepest one plus one is exactly "layers proven in a single subject" — the
     * number the product has always quoted as a deepest dive.
     *
     * @var array<string, array{0: string, 1: string|null}>
     */
    private const DEFINITIONS = [
        self::BOARD_XP => ['sum(xp_events.amount)', null],
        self::BOARD_LAYERS => ['count(*)', XpEvent::TYPE_LAYER_COMPLETED],
        self::BOARD_CONCEPTS => ['count(*)', XpEvent::TYPE_CONCEPT_MASTERED],
        self::BOARD_DEPTH => ['max(xp_events.depth) + 1', XpEvent::TYPE_LAYER_COMPLETED],
        self::BOARD_SUBJECTS => ['count(distinct xp_events.conversation_id)', XpEvent::TYPE_LAYER_COMPLETED],
        self::BOARD_SURFACED => ['count(*)', XpEvent::TYPE_SUBJECT_SURFACED],
    ];

    /**
     * One page of a board, ranked. Cached: nobody needs a leaderboard to the
     * second, and this is the only query on the page that grows with the user
     * base.
     *
     * @return Collection<int, array{rank: int, value: int, user: User}>
     */
    public function board(string $board, string $period, ?int $limit = null): Collection
    {
        $limit ??= (int) config('platform.ranking.per_board');

        $rows = $this->ranked($board, $period)
            ->orderByDesc('value')
            // Ties go to whoever has been descending longest, then by id so
            // the order is total — a board that reshuffles on reload reads
            // as broken however correct the numbers are.
            ->orderBy('first_at')
            ->orderBy('xp_events.user_id')
            ->limit($limit)
            ->get();
        cache()->remember(
            "dth:ranking:{$this->version()}:{$this->resolveBoard($board)}:{$this->resolvePeriod($period)}:{$limit}",
            (int) config('platform.ranking.cache_ttl'),
            fn () => $rows->count(),
        );

        return $this->withUsers($rows);
    }

    /**
     * Where one learner stands — computed live, never cached, because this is
     * the number they are actually watching. Works for learners who have not
     * joined the boards: they are ranked against those who have, which is the
     * honest answer to "where would I be".
     *
     * Counting only those strictly ahead gives equal scores an equal rank, which
     * is exactly what the visible board shows. Any cleverer tie-break here would
     * put a different number under the learner's own row than beside it.
     *
     * @return array{value: int, rank: int|null, gap: int|null}
     */
    public function standing(User $user, string $board, string $period): array
    {
        // `first()` and read the column by name — `value('value')` would hand
        // back whatever the select lists first, which here is the user id.
        $row = $this->tally($board, $period)
            ->where('xp_events.user_id', $user->id)
            ->first();

        $value = (int) ($row->value ?? 0);

        if ($value <= 0) {
            return ['value' => 0, 'rank' => null, 'gap' => null];
        }

        // Count and gap in one pass: both answer "who is above me", and two
        // queries for one question would be two chances to disagree.
        $above = DB::query()
            ->fromSub($this->ranked($board, $period), 'boards')
            ->where('boards.value', '>', $value)
            ->selectRaw('count(*) as ahead, min(boards.value) as next_value')
            ->first();

        $next = $above?->next_value;

        return [
            'value' => $value,
            'rank' => (int) ($above->ahead ?? 0) + 1,
            // How much more is one place up — null at the top of the board, and
            // null on an empty one. A rank on its own says where you are; this
            // says whether the next place is one evening away or a season.
            'gap' => $next !== null ? (int) $next - $value : null,
        ];
    }

    /** Forget every cached board. Call it when the boards gain or lose a learner. */
    public function forgetBoards(): void
    {
        Cache::forever(self::VERSION_KEY, $this->version() + 1);
    }

    private function version(): int
    {
        return (int) Cache::get(self::VERSION_KEY, 0);
    }

    /**
     * Every board this learner appears on, best rank first — the "what am I
     * actually good at" summary that makes a profile worth opening.
     *
     * @return Collection<int, array{board: string, rank: int, value: int, gap: int|null}>
     */
    public function standings(User $user, string $period = self::PERIOD_ALL): Collection
    {
        return collect(self::BOARDS)
            ->map(fn (string $board) => ['board' => $board] + $this->standing($user, $board, $period))
            ->filter(fn (array $standing) => $standing['rank'] !== null)
            ->sortBy('rank')
            ->values();
    }

    /** How many learners stand on the boards at all — the "who am I up against". */
    public function participants(): int
    {
        return Cache::remember(
            "dth:ranking:{$this->version()}:participants",
            (int) config('platform.ranking.cache_ttl'),
            fn () => User::query()->where('ranked', true)->where('enabled', true)->count(),
        );
    }

    /**
     * Standard competition ranking: equal scores share a rank and the next one
     * skips (1, 1, 3). Two learners with identical records shown as #4 and #5
     * is a made-up difference, and everyone can see it is made up.
     *
     * @param  Collection<int, object>  $rows
     * @return Collection<int, array{rank: int, value: int, user: User}>
     */
    private function withUsers(Collection $rows): Collection
    {
        $users = User::query()->whereIn('id', $rows->pluck('user_id'))->get()->keyBy('id');
        $rank = 0;
        $previous = null;

        return $rows
            ->filter(fn (object $row) => $users->has($row->user_id))
            ->values()
            ->map(function (object $row, int $index) use ($users, &$rank, &$previous) {
                $value = (int) $row->value;

                if ($value !== $previous) {
                    $rank = $index + 1;
                    $previous = $value;
                }

                return ['rank' => $rank, 'value' => $value, 'user' => $users->get($row->user_id)];
            });
    }

    /** The aggregate for every learner, opted in or not. */
    private function tally(string $board, string $period): Builder
    {
        [$expression, $type] = self::DEFINITIONS[$this->resolveBoard($board)];

        return DB::table('xp_events')
            ->selectRaw("xp_events.user_id, {$expression} as value, min(xp_events.created_at) as first_at")
            ->when($type !== null, fn (Builder $query) => $query->where('xp_events.type', $type))
            ->when($this->since($period), fn (Builder $query, Carbon $since) => $query->where('xp_events.created_at', '>=', $since))
            ->groupBy('xp_events.user_id')
            ->having('value', '>', 0);
    }

    /** The same aggregate, narrowed to learners who chose to be on the boards. */
    private function ranked(string $board, string $period): Builder
    {
        return $this->tally($board, $period)
            ->join('users', 'users.id', '=', 'xp_events.user_id')
            ->where('users.ranked', true)
            ->where('users.enabled', true);
    }

    /** Calendar windows, not rolling ones: "this week" should mean this week. */
    private function since(string $period): ?Carbon
    {
        return match ($this->resolvePeriod($period)) {
            self::PERIOD_MONTH => now()->startOfMonth(),
            self::PERIOD_WEEK => now()->startOfWeek(),
            default => null,
        };
    }

    /**
     * A board name selects a raw aggregate expression, so it is resolved against
     * the known set here as well as validated at the request boundary. Two
     * cheap checks are the right price for raw SQL.
     */
    private function resolveBoard(string $board): string
    {
        return in_array($board, self::BOARDS, true) ? $board : self::BOARD_XP;
    }

    private function resolvePeriod(string $period): string
    {
        return in_array($period, self::PERIODS, true) ? $period : self::PERIOD_ALL;
    }
}
