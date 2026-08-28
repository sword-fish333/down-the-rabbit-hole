@php($user = auth()->user())

{{-- Where the reader stands, before they read anyone else's name.

     Shown whether or not they are on the boards — a learner who has not joined
     still gets told exactly where they would land, which is a far better
     invitation than a wall of strangers and a switch. --}}
<div @class([
    'rounded-2xl border p-4 sm:p-5',
    'border-primary/30 bg-primary/8' => $standing['rank'] !== null,
    'border-border/70 bg-surface/40' => $standing['rank'] === null,
])>
    <div class="flex flex-wrap items-center gap-x-4 gap-y-3">
        <x-frontend.avatar :user="$user" size="h-11 w-11" text="text-sm" />

        <div class="min-w-0 flex-1">
            <p class="dth-coord">{{ __('frontend.rankings.your-standing') }}</p>

            @if ($standing['rank'] === null)
                <p class="mt-0.5 font-display text-base font-semibold text-foreground">{{ __('frontend.rankings.unranked-value') }}</p>
                <p class="mt-0.5 text-xs text-foreground-muted">{{ __('frontend.rankings.unranked-hint') }}</p>
            @else
                <p class="mt-0.5 flex flex-wrap items-baseline gap-x-2.5 gap-y-1">
                    <x-frontend.rank-badge :rank="$standing['rank']" class="translate-y-px" />
                    <span class="font-display text-lg font-semibold tabular-nums text-foreground">
                        {{ trans_choice('frontend.rankings.board.'.$board.'-unit', $standing['value'], ['count' => number_format($standing['value'])]) }}
                    </span>
                </p>

                {{-- The distance to one place up. A rank alone says where you
                     are; this says whether the next place is an evening away or
                     a season — which is the only part of a leaderboard that is
                     any use to the person reading it. --}}
                @if ($standing['gap'] !== null)
                    <p class="mt-1 flex items-center gap-1.5 text-xs text-foreground-muted">
                        <span class="material-symbols-outlined text-[0.95rem] text-primary" aria-hidden="true">trending_up</span>
                        {{ __('frontend.rankings.gap', [
                            'value' => trans_choice('frontend.rankings.board.'.$board.'-unit', $standing['gap'], ['count' => number_format($standing['gap'])]),
                        ]) }}
                    </p>
                @elseif ($standing['rank'] === 1)
                    <p class="mt-1 flex items-center gap-1.5 text-xs text-foreground-muted">
                        <span class="material-symbols-outlined text-[0.95rem] text-accent" aria-hidden="true">workspace_premium</span>
                        {{ __('frontend.rankings.leading') }}
                    </p>
                @endif

                @unless ($user->isRanked())
                    <p class="mt-1 flex items-center gap-1.5 text-xs text-foreground-muted">
                        <span class="material-symbols-outlined text-[0.95rem]" aria-hidden="true">visibility_off</span>
                        {{ __('frontend.rankings.hidden-hint') }}
                    </p>
                @endunless
            @endif
        </div>

        {{-- Joining and leaving are the same one-field post, so the switch can
             never disagree with the state it is describing. --}}
        <form method="POST" action="{{ route('profile.update-ranking') }}" class="shrink-0">
            @csrf
            <input type="hidden" name="ranked" value="{{ $user->isRanked() ? '0' : '1' }}">

            <button type="submit" @class([
                'inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold transition duration-(--motion-feedback) ease-(--ease-snap) focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring',
                'border border-border-strong text-foreground-muted hover:border-danger/50 hover:text-foreground' => $user->isRanked(),
                'bg-primary text-primary-foreground hover:bg-primary/90' => ! $user->isRanked(),
            ])>
                <span class="material-symbols-outlined text-[1.1rem]" aria-hidden="true">{{ $user->isRanked() ? 'visibility_off' : 'groups' }}</span>
                {{ $user->isRanked() ? __('frontend.rankings.leave-cta') : __('frontend.rankings.join-cta') }}
            </button>
        </form>
    </div>

    @unless ($user->isRanked())
        <p class="mt-3 border-t border-border/50 pt-3 text-xs leading-relaxed text-foreground-muted">
            {{ __('frontend.rankings.join-body') }}
        </p>
    @endunless
</div>
