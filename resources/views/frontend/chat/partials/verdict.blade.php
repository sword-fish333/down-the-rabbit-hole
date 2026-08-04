@php
    $passed = $attempt->passed();
    $hasMisconception = $attempt->hasMisconception();
    $gap = $attempt->calibrationGap();
@endphp

{{-- A persisted verdict, re-rendered on load. Deliberately the same shape
     chat.js builds live (see renderVerdict) so a reloaded transcript reads
     identically to the session the learner sat through.

     Three distinct outcomes, never collapsed into "wrong": passed, incomplete
     (the idea wasn't reached), and misconception detected (something believed is
     actively false). They call for different next moves. --}}
<details open @class([
    'group/verdict mt-3 rounded-2xl border p-4',
    'border-success/35 bg-success/6' => $passed,
    'border-accent/35 bg-accent/6' => ! $passed,
])>
    <summary data-verdict-toggle
             class="-m-1 flex cursor-pointer select-none list-none items-center gap-2 rounded-xl p-1 transition-colors duration-(--motion-feedback) ease-(--ease-snap) hover:bg-surface-muted/50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring sm:gap-3">
        <span @class([
            'flex min-w-0 flex-1 items-center gap-2 font-display text-sm font-semibold',
            'text-success' => $passed,
            'text-accent' => ! $passed,
        ])>
            <span class="material-symbols-outlined text-[1.15rem]" aria-hidden="true">
                {{ $passed ? 'check_circle' : ($hasMisconception ? 'error' : 'incomplete_circle') }}
            </span>
            @if ($passed)
                {{ __('frontend.chat.verdict-pass') }}
            @elseif ($hasMisconception)
                {{ __('frontend.chat.verdict-misconception') }}
            @else
                {{ __('frontend.chat.verdict-incomplete') }}
            @endif
        </span>
        <span class="dth-coord shrink-0">{{ __('frontend.chat.score') }} {{ $attempt->score }}/100</span>
        <span class="material-symbols-outlined shrink-0 text-[1.1rem] text-foreground-muted transition-transform duration-(--motion-state) ease-(--ease-out) group-open/verdict:rotate-180"
              aria-hidden="true">expand_more</span>
        <span class="sr-only">{{ __('frontend.chat.verdict-toggle') }}</span>
    </summary>

    <div class="pt-3 [&>*:first-child]:mt-0">

        @if ($attempt->feedback)
            <x-frontend.markdown :content="$attempt->feedback" class="mt-3 text-[0.95rem]" />
        @endif

        @if (filled($attempt->criteria))
            <ul class="mt-4 space-y-2">
                @foreach ($attempt->criteria as $criterion)
                    <li class="flex items-start gap-2 text-sm">
                        <span @class([
                            'material-symbols-outlined mt-px text-[1.05rem]',
                            'text-success' => $criterion['met'] ?? false,
                            'text-foreground-muted' => ! ($criterion['met'] ?? false),
                        ]) aria-hidden="true">{{ ($criterion['met'] ?? false) ? 'check' : 'remove' }}</span>
                        <span>
                            <span class="{{ ($criterion['met'] ?? false) ? 'text-foreground' : 'text-foreground-muted' }}">
                                {{ $criterion['name'] ?? '' }}
                            </span>
                            <span class="sr-only">— {{ ($criterion['met'] ?? false) ? __('frontend.chat.criterion-met') : __('frontend.chat.criterion-unmet') }}</span>
                            @if (filled($criterion['note'] ?? null))
                                <span class="block text-xs text-foreground-muted/80">{{ $criterion['note'] }}</span>
                            @endif
                        </span>
                    </li>
                @endforeach
            </ul>
        @endif

        @if ($hasMisconception)
            <div class="mt-4 space-y-2">
                @foreach ($attempt->misconceptions as $misconception)
                    <div class="dth-misconception rounded-r-xl px-3.5 py-2.5 text-sm">
                        <p class="font-medium text-foreground">{{ $misconception['concept'] ?? '' }}</p>
                        @if (filled($misconception['belief'] ?? null))
                            <p class="mt-1 text-xs text-foreground-muted">
                                {{ __('frontend.chat.you-thought') }} {{ $misconception['belief'] }}
                            </p>
                        @endif
                        @if (filled($misconception['correction'] ?? null))
                            <p class="mt-1 text-foreground/90">{{ $misconception['correction'] }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        @if ($gap !== null)
            <p class="dth-coord mt-4">
                @if (abs($gap) <= 15)
                    {{ __('frontend.chat.calibration-good') }}
                @elseif ($gap > 0)
                    {{ __('frontend.chat.calibration-over') }}
                @else
                    {{ __('frontend.chat.calibration-under') }}
                @endif
            </p>
        @endif
    </div>
</details>
