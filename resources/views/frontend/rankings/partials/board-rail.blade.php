{{-- The six boards.

     A horizontal, scrollable strip on a phone and a vertical rail from `lg` —
     one list, one markup, two shapes. Each entry names its measure and, where
     it is on screen, the one line explaining what it counts, because a
     leaderboard nobody understands the rules of is just a wall of names. --}}
<nav aria-label="{{ __('frontend.rankings.boards-label') }}" class="lg:sticky lg:top-24">
    <h2 class="dth-coord mb-3 hidden lg:block">{{ __('frontend.rankings.boards-label') }}</h2>

    <ul class="scrollbar-slim -mx-4 flex gap-2 overflow-x-auto px-4 pb-2 lg:mx-0 lg:flex-col lg:gap-1 lg:overflow-visible lg:px-0 lg:pb-0">
        @foreach ($boards as $key => $icon)
            @php($active = $board === $key)

            <li class="shrink-0 lg:shrink">
                <a href="{{ route('rankings.index', ['board' => $key, 'period' => $period]) }}"
                   @if ($active) aria-current="page" @endif
                   @class([
                       'flex items-center gap-2.5 rounded-xl border px-3 py-2.5 text-sm transition duration-(--motion-feedback) ease-(--ease-snap) focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring lg:border-transparent lg:px-3',
                       'border-primary/40 bg-primary/10 font-medium text-primary lg:border-transparent lg:bg-primary/10' => $active,
                       'border-border bg-surface/40 text-foreground-muted hover:bg-surface-muted hover:text-foreground' => ! $active,
                   ])>
                    <span class="material-symbols-outlined shrink-0 text-[1.15rem]" aria-hidden="true">{{ $icon }}</span>
                    <span class="whitespace-nowrap lg:whitespace-normal">{{ __('frontend.rankings.board.'.$key) }}</span>
                </a>
            </li>
        @endforeach
    </ul>
</nav>
