@php
    use App\Services\Gamification\RankingService;

    $boardIcons = [
        RankingService::BOARD_XP => 'bolt',
        RankingService::BOARD_LAYERS => 'stairs',
        RankingService::BOARD_CONCEPTS => 'verified',
        RankingService::BOARD_DEPTH => 'south_east',
        RankingService::BOARD_SUBJECTS => 'forum',
        RankingService::BOARD_SURFACED => 'workspace_premium',
    ];
@endphp

<x-frontend.layout :title="__('frontend.learners.title', ['name' => $learner->fullName()])"
                   :description="__('frontend.learners.meta', ['name' => $learner->fullName()])" shell>
    {{-- ===================================================================
         One learner's record.

         Deliberately the same six tiles as the private profile, in the same
         order: what someone sees about you here is exactly what you see about
         yourself, minus the parts that are nobody's business. "To revisit" is
         the one number that stays home — a count of what you still have wrong is
         a working note, not a public record.
         =================================================================== --}}
    <section class="mx-auto max-w-4xl px-4 py-10 sm:px-6 lg:px-8">
        <a href="{{ route('rankings.index') }}"
           class="dth-coord inline-flex items-center gap-1.5 rounded-lg text-foreground-muted transition hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
            <span class="material-symbols-outlined text-[1rem]" aria-hidden="true">arrow_back</span>
            {{ __('frontend.learners.back-to-rankings') }}
        </a>

        {{-- Their own page, before they have joined: a preview, labelled as one.
             Seeing the thing is a better explanation of what joining publishes
             than any amount of copy about it. --}}
        @if ($isSelf && ! $learner->isRanked())
            <div class="mt-5 flex items-start gap-3 rounded-2xl border border-accent/30 bg-accent/8 px-4 py-3">
                <span class="material-symbols-outlined shrink-0 text-[1.15rem] text-accent" aria-hidden="true">visibility_off</span>
                <div class="min-w-0">
                    <p class="text-sm font-medium text-foreground">{{ __('frontend.learners.preview-title') }}</p>
                    <p class="mt-0.5 text-xs leading-relaxed text-foreground-muted">{{ __('frontend.learners.preview-body') }}</p>
                </div>
            </div>
        @endif

        {{-- Identity --}}
        <div class="mt-6 flex flex-col items-start gap-5 sm:flex-row sm:items-center">
            <x-frontend.avatar :user="$learner" size="h-20 w-20" text="text-xl" />

            <div class="min-w-0">
                <h1 class="truncate font-display text-2xl font-semibold tracking-tight text-foreground">{{ $learner->fullName() }}</h1>
                <p class="mt-1.5 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-foreground-muted">
                    <span class="inline-flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[0.95rem] text-primary" aria-hidden="true">bolt</span>
                        {{ __('frontend.profile.xp', ['xp' => number_format($record['xp'])]) }}
                    </span>
                    @if ($streak && $streak->current_count > 0)
                        <span class="inline-flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[0.95rem] text-accent" aria-hidden="true">local_fire_department</span>
                            {{ trans_choice('frontend.navbar.descent-days', $streak->current_count, ['count' => $streak->current_count]) }}
                        </span>
                    @endif
                    <span class="inline-flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[0.95rem]" aria-hidden="true">schedule</span>
                        {{ __('frontend.learners.since', ['date' => $learner->created_at->isoFormat('MMMM YYYY')]) }}
                    </span>
                </p>
            </div>
        </div>

        {{-- The record. Same tiles, same order, same words as their own page. --}}
        <div class="mt-9 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <x-frontend.stat :label="__('frontend.profile.record.layers')" :value="$record['layers']"
                             icon="stairs" tone="primary" :hint="__('frontend.profile.record.layers-hint')" />
            <x-frontend.stat :label="__('frontend.profile.record.mastered')" :value="$record['mastered']"
                             icon="verified" tone="success" :hint="__('frontend.profile.record.mastered-hint')" />
            <x-frontend.stat :label="__('frontend.profile.record.deepest')" :value="$record['deepest']"
                             icon="south_east" tone="primary" :hint="__('frontend.profile.record.deepest-hint')" />
            <x-frontend.stat :label="__('frontend.profile.record.subjects')" :value="$record['subjects']" icon="forum" tone="muted" />
            <x-frontend.stat :label="__('frontend.profile.record.surfaced')" :value="$record['surfaced']"
                             icon="workspace_premium" tone="success" />
            <x-frontend.stat :label="__('frontend.rankings.board.xp')" :value="number_format($record['xp'])"
                             icon="bolt" tone="accent" />
        </div>

        {{-- Where they stand. The cross-link that makes a profile worth opening
             from a board and a board worth opening from a profile. --}}
        <section class="mt-9" aria-labelledby="dth-learner-standings">
            <h2 id="dth-learner-standings" class="dth-coord mb-3">{{ __('frontend.rankings.best-standings') }}</h2>

            @if ($standings->isEmpty())
                <p class="text-sm text-foreground-muted">{{ __('frontend.rankings.no-standings') }}</p>
            @else
                <ul class="flex flex-wrap gap-2">
                    @foreach ($standings as $standing)
                        <li>
                            <a href="{{ route('rankings.index', ['board' => $standing['board']]) }}"
                               class="dth-card flex items-center gap-2 rounded-xl border border-border bg-surface/40 px-3 py-2 text-sm text-foreground-muted transition hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                <span class="material-symbols-outlined text-[1.05rem] text-primary" aria-hidden="true">{{ $boardIcons[$standing['board']] }}</span>
                                {{ __('frontend.rankings.board.'.$standing['board']) }}
                                <x-frontend.rank-badge :rank="$standing['rank']" />
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        {{-- Deepest dive — the line the product exists to let someone say. --}}
        @if ($deepestDive && $deepestDive->layersCleared() > 0)
            <div class="mt-8 flex items-center gap-3 rounded-2xl border border-accent/30 bg-accent/8 px-4 py-3">
                <span class="material-symbols-outlined shrink-0 text-[1.2rem] text-accent" aria-hidden="true">military_tech</span>
                <span class="min-w-0">
                    <span class="dth-coord block">{{ __('frontend.profile.record.deepest-dive') }}</span>
                    <span class="block truncate text-sm font-medium text-foreground">
                        {{ trans_choice('frontend.profile.record.deepest-dive-value', $deepestDive->layersCleared(), [
                            'subject' => $deepestDive->displayTitle(),
                            'count' => $deepestDive->layersCleared(),
                        ]) }}
                    </span>
                </span>
            </div>
        @endif

        {{-- Published descents. Already readable by anyone holding the link, so
             listing them here exposes nothing new — it just makes them findable. --}}
        <section class="mt-10" aria-labelledby="dth-learner-shared">
            <h2 id="dth-learner-shared" class="font-display text-base font-semibold text-foreground">{{ __('frontend.learners.shared-title') }}</h2>
            <p class="mt-1 text-sm text-foreground-muted">
                {{ $shared->isEmpty()
                    ? __('frontend.learners.shared-empty', ['name' => $learner->fullName()])
                    : __('frontend.learners.shared-body', ['name' => $learner->fullName()]) }}
            </p>

            @if ($shared->isNotEmpty())
                <ul class="mt-4 grid gap-3 sm:grid-cols-2">
                    @foreach ($shared as $subject)
                        <li>
                            <a href="{{ route('subject.shared', $subject->share_token) }}"
                               class="dth-card group flex items-center gap-3 rounded-2xl border border-border/70 bg-surface/40 px-4 py-3 backdrop-blur-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-medium text-foreground">{{ $subject->displayTitle() }}</span>
                                    <span class="dth-coord mt-0.5 block">
                                        {{ trans_choice('frontend.rankings.board.depth-unit', $subject->layersCleared(), ['count' => $subject->layersCleared()]) }}
                                    </span>
                                </span>
                                <span class="dth-card-arrow material-symbols-outlined shrink-0 text-[1.1rem] text-foreground-muted" aria-hidden="true">arrow_forward</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </section>
</x-frontend.layout>
