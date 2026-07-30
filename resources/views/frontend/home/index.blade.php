<x-frontend.layout :description="__('frontend.meta.description')">
    {{-- ===================================================================
         Home — the front door.

         One job: get a subject typed and the first layer opened. Everything
         else on this page is subordinate to that, which is why the composer is
         above the fold, ungated, and pre-filled with a sensible mode.

         This is the ONE page allowed ambient movement and scroll reveals. The
         session that follows is deliberately quieter.
         =================================================================== --}}
    <section class="relative mx-auto flex min-h-[calc(100svh-4rem)] max-w-3xl flex-col items-center justify-center px-4 py-16 text-center sm:px-6">

        {{-- A learner mid-descent gets offered the way back in before anything
             else. Resuming beats starting: it is the behaviour that correlates
             with actually finishing something. --}}
        @if ($resumable)
            <a href="{{ route('hole.show', $resumable) }}"
               class="dth-card group mb-8 inline-flex max-w-full items-center gap-3 rounded-2xl border border-primary/30 bg-primary/8 px-4 py-2.5 text-left focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                <span class="material-symbols-outlined shrink-0 text-[1.2rem] text-primary" aria-hidden="true">play_circle</span>
                <span class="min-w-0">
                    <span class="dth-coord block">{{ __('frontend.home.resume') }} · {{ __('frontend.chat.depth') }} {{ $resumable->current_depth }}</span>
                    <span class="block truncate text-sm font-medium text-foreground">{{ $resumable->displayTitle() }}</span>
                </span>
                <span class="dth-card-arrow material-symbols-outlined shrink-0 text-[1.1rem] text-primary" aria-hidden="true">arrow_forward</span>
            </a>
        @endif

        {{-- Kicker --}}
        <span class="mb-6 inline-flex items-center gap-2 rounded-full border border-primary/30 bg-primary/8 px-3.5 py-1 font-mono text-xs uppercase tracking-[0.18em] text-primary">
            <span class="material-symbols-outlined text-[1rem]" aria-hidden="true">arrow_downward</span>
            {{ __('frontend.home.kicker') }}
        </span>

        <h1 class="text-balance font-display text-4xl font-semibold leading-[1.05] tracking-tight text-foreground sm:text-5xl md:text-6xl">
            {{ __('frontend.home.title') }}
        </h1>
        <p class="mt-5 max-w-xl text-pretty text-base text-foreground-muted sm:text-lg">
            {{ __('frontend.home.subtitle') }}
        </p>

        {{-- ---------------------------------------------------------------
             The composer. Quiet border by default; a one-time cyan lock-on
             trace when it takes focus (see .dth-composer). No pulsing, no
             permanent glow — you are about to type a paragraph in here.
             --------------------------------------------------------------- --}}
        <form action="{{ route('descend') }}" method="POST" class="mt-10 w-full" data-descend>
            @csrf

            <div class="dth-composer rounded-2xl border border-border-strong bg-surface/70 p-2 shadow-xl backdrop-blur-md">
                <label for="dth-prompt" class="sr-only">{{ __('frontend.home.placeholder-label') }}</label>
                <textarea
                    id="dth-prompt"
                    name="prompt"
                    rows="2"
                    required
                    minlength="2"
                    maxlength="500"
                    data-autogrow
                    data-submit-on-enter
                    aria-describedby="dth-prompt-hint"
                    placeholder="{{ __('frontend.home.placeholder') }}"
                    class="block w-full resize-none border-0 bg-transparent px-4 py-3 text-base text-foreground placeholder:text-foreground-muted/70 focus:outline-none"></textarea>

                <div class="flex flex-col gap-3 px-2 pb-1 pt-2 sm:flex-row sm:items-center sm:justify-between">
                    <p id="dth-prompt-hint" class="flex items-center gap-1.5 text-left font-mono text-xs text-foreground-muted/70">
                        <span class="material-symbols-outlined text-[1rem] text-success" aria-hidden="true">neurology</span>
                        {{ __('frontend.home.composer-hint') }}
                    </p>

                    <button type="submit" data-descend-cta
                            data-loading-label="{{ __('frontend.home.cta-loading') }}"
                            class="group/cta inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground transition duration-(--motion-feedback) ease-(--ease-snap) hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:cursor-wait disabled:opacity-70">
                        <span data-cta-label>{{ __('frontend.home.cta') }}</span>
                        <span class="dth-cta-arrow material-symbols-outlined text-[1.15rem]" aria-hidden="true">south_east</span>
                    </button>
                </div>
            </div>

            @error('prompt')
                <p class="mt-2 flex items-center justify-center gap-1 text-xs text-danger" role="alert">
                    <span class="material-symbols-outlined text-[0.95rem]" aria-hidden="true">error</span>{{ $message }}
                </p>
            @enderror

            {{-- Mode picker, collapsed. The default is already correct, so this
                 never blocks the primary action. --}}
            <div class="mt-4 rounded-2xl border border-border/60 bg-surface/25 px-3 py-1.5">
                <x-frontend.mode-picker :modes="$modes" />
            </div>
        </form>

        {{-- One-tap rabbit holes — the lowest-friction way to start at all. --}}
        <div class="mt-8 flex flex-col items-center gap-3">
            <span class="dth-coord">{{ __('frontend.home.topics-label') }}</span>
            <div class="flex flex-wrap items-center justify-center gap-2">
                @foreach (__('frontend.home.topics') as $index => $topic)
                    <button type="button" data-topic="{{ $topic }}" style="--i: {{ $index }}"
                            class="dth-stagger rounded-full border border-border bg-surface/50 px-3.5 py-1.5 text-sm text-foreground-muted transition duration-(--motion-feedback) ease-(--ease-snap) hover:border-primary/50 hover:bg-primary/8 hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                        {{ $topic }}
                    </button>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ---------------------------------------------------------------
         Below the fold: what the mechanic actually is. Sparse scroll
         reveals only — one per section, and only where support exists.
         --------------------------------------------------------------- --}}
    <section class="mx-auto max-w-5xl px-4 pb-24 sm:px-6" aria-labelledby="dth-how-heading">
        <h2 id="dth-how-heading" class="dth-reveal text-balance text-center font-display text-2xl font-semibold tracking-tight text-foreground sm:text-3xl">
            {{ __('frontend.home.how-title') }}
        </h2>
        <p class="dth-reveal mx-auto mt-3 max-w-2xl text-pretty text-center text-sm text-foreground-muted sm:text-base">
            {{ __('frontend.home.how-body') }}
        </p>

        <ol class="mt-12 grid gap-4 sm:grid-cols-3">
            @foreach ([
                ['icon' => 'edit_note', 'key' => 'name'],
                ['icon' => 'quiz', 'key' => 'prove'],
                ['icon' => 'stairs', 'key' => 'descend'],
            ] as $index => $step)
                <li class="dth-reveal dth-card relative rounded-2xl border border-border/70 bg-surface/40 p-5 backdrop-blur-sm">
                    <span class="dth-coord">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</span>
                    <span class="mt-3 grid h-10 w-10 place-items-center rounded-xl bg-primary/10 text-primary ring-1 ring-primary/20">
                        <span class="material-symbols-outlined text-[1.25rem]" aria-hidden="true">{{ $step['icon'] }}</span>
                    </span>
                    <h3 class="mt-4 font-display text-sm font-semibold text-foreground">
                        {{ __('frontend.home.steps.'.$step['key'].'.title') }}
                    </h3>
                    <p class="mt-1.5 text-sm leading-relaxed text-foreground-muted">
                        {{ __('frontend.home.steps.'.$step['key'].'.body') }}
                    </p>
                </li>
            @endforeach
        </ol>

        {{-- Feature cards. Hover is 2px of lift and a border, nothing more —
             and keyboard focus gets exactly the same treatment. --}}
        <div class="mt-16 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['icon' => 'flowsheet', 'key' => 'flow'],
                ['icon' => 'psychology', 'key' => 'mastery'],
                ['icon' => 'history', 'key' => 'review'],
                ['icon' => 'tune', 'key' => 'modes'],
            ] as $feature)
                <div class="dth-reveal dth-card rounded-2xl border border-border/70 bg-surface/40 p-5 backdrop-blur-sm">
                    <span class="material-symbols-outlined text-[1.35rem] text-primary" aria-hidden="true">{{ $feature['icon'] }}</span>
                    <h3 class="mt-3 font-display text-sm font-semibold text-foreground">
                        {{ __('frontend.home.features.'.$feature['key'].'.title') }}
                    </h3>
                    <p class="mt-1.5 text-sm leading-relaxed text-foreground-muted">
                        {{ __('frontend.home.features.'.$feature['key'].'.body') }}
                    </p>
                </div>
            @endforeach
        </div>
    </section>
</x-frontend.layout>
