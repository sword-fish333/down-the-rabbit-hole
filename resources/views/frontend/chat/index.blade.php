<x-frontend.layout :title="__('frontend.holes.title')">
    {{-- ===================================================================
         The learner's holes. Resume first, review second, start third —
         ordered by what actually correlates with learning something.
         =================================================================== --}}
    <section class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
        <x-frontend.verify-banner />

        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="font-display text-2xl font-semibold tracking-tight text-foreground sm:text-3xl">
                    {{ __('frontend.holes.title') }}
                </h1>
                <p class="mt-1.5 text-sm text-foreground-muted">{{ __('frontend.holes.subtitle') }}</p>
            </div>

            <a href="{{ route('home') }}"
               class="group/cta inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition duration-(--motion-feedback) ease-(--ease-snap) hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                {{ __('frontend.holes.new') }}
                <span class="dth-cta-arrow material-symbols-outlined text-[1.1rem]" aria-hidden="true">south_east</span>
            </a>
        </div>

        @if ($holes->isEmpty())
            {{-- Empty state does one job: get them into the composer. --}}
            <div class="mt-12 rounded-2xl border border-dashed border-border-strong bg-surface/30 px-6 py-14 text-center">
                <span class="material-symbols-outlined text-[2rem] text-primary/70" aria-hidden="true">stairs</span>
                <h2 class="mt-3 font-display text-lg font-semibold text-foreground">{{ __('frontend.holes.empty-title') }}</h2>
                <p class="mx-auto mt-2 max-w-md text-sm text-foreground-muted">{{ __('frontend.holes.empty-body') }}</p>
                <a href="{{ route('home') }}"
                   class="mt-6 inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90">
                    {{ __('frontend.holes.empty-cta') }}
                    <span class="material-symbols-outlined text-[1.1rem]" aria-hidden="true">south_east</span>
                </a>
            </div>
        @else
            <ul class="mt-8 grid gap-4 sm:grid-cols-2">
                @foreach ($holes as $hole)
                    @php
                        $maxDepth = (int) config('platform.chat.max_depth');
                        $progress = $maxDepth > 0 ? min(100, round($hole->current_depth / $maxDepth * 100)) : 0;
                    @endphp

                    <li style="--i: {{ $loop->index }}" class="dth-stagger">
                        <a href="{{ route('hole.show', $hole) }}"
                           class="dth-card flex h-full flex-col rounded-2xl border border-border/70 bg-surface/40 p-5 backdrop-blur-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                            <div class="flex items-start justify-between gap-3">
                                <p class="dth-coord">
                                    {{ __('frontend.chat.depth') }} {{ str_pad($hole->current_depth, 2, '0', STR_PAD_LEFT) }}<span aria-hidden="true">/</span>{{ str_pad($maxDepth, 2, '0', STR_PAD_LEFT) }}
                                </p>

                                {{-- Status: icon + word, never colour alone. --}}
                                @if ($hole->isSurfaced())
                                    <span class="inline-flex items-center gap-1 rounded-full border border-success/35 bg-success/8 px-2 py-0.5 text-xs text-success">
                                        <span class="material-symbols-outlined text-[0.9rem]" aria-hidden="true">workspace_premium</span>
                                        {{ __('frontend.holes.status.surfaced') }}
                                    </span>
                                @elseif ($hole->isCheckpointPending())
                                    <span class="inline-flex items-center gap-1 rounded-full border border-primary/35 bg-primary/8 px-2 py-0.5 text-xs text-primary">
                                        <span class="material-symbols-outlined text-[0.9rem]" aria-hidden="true">quiz</span>
                                        {{ __('frontend.holes.status.checkpoint') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-full border border-border px-2 py-0.5 text-xs text-foreground-muted">
                                        <span class="material-symbols-outlined text-[0.9rem]" aria-hidden="true">south_east</span>
                                        {{ __('frontend.holes.status.exploring') }}
                                    </span>
                                @endif
                            </div>

                            <h2 class="mt-3 line-clamp-2 text-balance font-display text-base font-semibold text-foreground">
                                {{ $hole->displayTitle() }}
                            </h2>

                            <div class="mt-3 h-1 overflow-hidden rounded-full bg-surface-muted" role="presentation">
                                <div @class([
                                    'h-full rounded-full transition-[width] duration-(--motion-panel)',
                                    'bg-success' => $hole->isSurfaced(),
                                    'bg-primary' => ! $hole->isSurfaced(),
                                ]) style="width: {{ $progress }}%"></div>
                            </div>

                            <div class="mt-auto flex flex-wrap items-center gap-x-4 gap-y-1.5 pt-4 text-xs text-foreground-muted">
                                @if ($hole->learningMode)
                                    <span class="inline-flex items-center gap-1.5">
                                        <span class="material-symbols-outlined text-[0.95rem]" aria-hidden="true">{{ $hole->learningMode->icon }}</span>
                                        {{ $hole->learningMode->name }}
                                    </span>
                                @endif
                                @if ($hole->mastered_count)
                                    <span class="inline-flex items-center gap-1.5 text-success">
                                        <span class="material-symbols-outlined text-[0.95rem]" aria-hidden="true">verified</span>
                                        {{ trans_choice('frontend.holes.mastered', $hole->mastered_count, ['count' => $hole->mastered_count]) }}
                                    </span>
                                @endif
                                <span class="ml-auto">{{ $hole->updated_at->diffForHumans() }}</span>
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>

            <div class="mt-8">{{ $holes->links() }}</div>
        @endif
    </section>
</x-frontend.layout>
