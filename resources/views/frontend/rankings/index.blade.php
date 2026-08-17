@php
    use App\Services\Gamification\RankingService;

    // Board id => icon. The order is the service's; the iconography is the
    // interface's, and it lives here for the same reason the mode accents do.
    $boards = [
        RankingService::BOARD_XP => 'bolt',
        RankingService::BOARD_LAYERS => 'stairs',
        RankingService::BOARD_CONCEPTS => 'verified',
        RankingService::BOARD_DEPTH => 'south_east',
        RankingService::BOARD_SUBJECTS => 'forum',
        RankingService::BOARD_SURFACED => 'workspace_premium',
    ];
@endphp

<x-frontend.layout :title="__('frontend.rankings.title')" shell>
    {{-- ===================================================================
         The boards.

         Same shape as every other app screen: one column of content at a
         readable measure, with the navigation for this screen in a rail beside
         it. On a phone the rail becomes a scrollable strip above the table,
         because a leaderboard is read top to bottom and the boards are a filter,
         not a destination.

         Six measures rather than one, deliberately. A single ranking has one
         winner and a long tail of people who will never catch them; six say
         "there is more than one way to be good at this", which is the only kind
         of competition a learning tool should be running.
         =================================================================== --}}
    <section class="mx-auto w-full max-w-[72rem] px-4 py-8 sm:px-6 lg:px-8">
        <header class="max-w-2xl">
            <h1 class="font-display text-2xl font-semibold tracking-tight text-foreground sm:text-3xl">
                {{ __('frontend.rankings.title') }}
            </h1>
            <p class="mt-2 text-sm text-foreground-muted sm:text-base">{{ __('frontend.rankings.subtitle') }}</p>
            <p class="dth-coord mt-3">{{ trans_choice('frontend.rankings.participants', $participants, ['count' => number_format($participants)]) }}</p>
        </header>

        <div class="mt-8 grid gap-6 lg:grid-cols-[13.5rem_minmax(0,1fr)] lg:gap-10">
            @include('frontend.rankings.partials.board-rail', compact('boards', 'board', 'period'))

            <div class="min-w-0 space-y-5">
                {{-- What this board counts, stated before the names. --}}
                <div>
                    <h2 class="flex items-center gap-2 font-display text-lg font-semibold text-foreground">
                        <span class="material-symbols-outlined text-[1.25rem] text-primary" aria-hidden="true">{{ $boards[$board] }}</span>
                        {{ __('frontend.rankings.board.'.$board) }}
                    </h2>
                    <p class="mt-1.5 text-sm leading-relaxed text-foreground-muted">{{ __('frontend.rankings.board.'.$board.'-hint') }}</p>
                </div>

                {{-- Windows. Real links, so each one is somewhere you can be sent.
                     A monthly and a weekly board exist so that joining today is
                     not joining a race that was decided a year ago. --}}
                <div role="group" aria-label="{{ __('frontend.rankings.period-label') }}"
                     class="inline-flex items-center gap-0.5 rounded-xl border border-border/70 bg-surface/40 p-0.5">
                    @foreach (RankingService::PERIODS as $key)
                        @php($active = $period === $key)

                        <a href="{{ route('rankings.index', ['board' => $board, 'period' => $key]) }}"
                           @if ($active) aria-current="true" @endif
                           @class([
                               'rounded-[0.6rem] px-3 py-1.5 text-xs font-medium transition duration-(--motion-feedback) ease-(--ease-snap) focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring',
                               'bg-primary/12 text-primary' => $active,
                               'text-foreground-muted hover:text-foreground' => ! $active,
                           ])>
                            {{ __('frontend.rankings.period.'.$key) }}
                        </a>
                    @endforeach
                </div>

                @include('frontend.rankings.partials.standing', compact('standing', 'board'))

                @if ($rows->isEmpty())
                    <div class="rounded-2xl border border-border/70 bg-surface/40 px-6 py-14 text-center backdrop-blur-sm">
                        <span class="material-symbols-outlined text-[1.6rem] text-foreground-muted" aria-hidden="true">leaderboard</span>
                        <p class="mt-3 font-display text-base font-semibold text-foreground">{{ __('frontend.rankings.empty-title') }}</p>
                        <p class="mx-auto mt-1.5 max-w-sm text-sm text-foreground-muted">{{ __('frontend.rankings.empty-body') }}</p>
                    </div>
                @else
                    @include('frontend.rankings.partials.board-table', compact('rows', 'board', 'period'))
                @endif
            </div>
        </div>
    </section>
</x-frontend.layout>
