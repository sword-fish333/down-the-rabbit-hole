@php
    use App\Models\Message;

    $maxDepth = (int) config('platform.chat.max_depth');
    $autostream = session('stream') && ! $conversation->isSurfaced();
    $progress = $maxDepth > 0 ? min(100, round($conversation->current_depth / $maxDepth * 100)) : 0;
    $source = $conversation->sources->first();
    $isOwner = auth()->check() && $conversation->user_id === auth()->id();

    // Strings chat.js needs. Passed as one JSON blob rather than a dozen data-*
    // attributes, so the localisation stays in lang/ and out of the JS.
    $strings = [
        'thinking' => __('frontend.chat.thinking'),
        'thoughtFor' => __('frontend.chat.thought-for'),
        'analyzing' => __('frontend.chat.analyzing'),
        'checkpointReady' => __('frontend.chat.checkpoint-ready'),
        'surfaced' => __('frontend.chat.surfaced-title'),
        'yourAnswer' => __('frontend.chat.your-answer'),
        'verdictPass' => __('frontend.chat.verdict-pass'),
        'verdictIncomplete' => __('frontend.chat.verdict-incomplete'),
        'verdictMisconception' => __('frontend.chat.verdict-misconception'),
        'verdictToggle' => __('frontend.chat.verdict-toggle'),
        'scoreLabel' => __('frontend.chat.score'),
        'youThought' => __('frontend.chat.you-thought'),
        'calibrationGood' => __('frontend.chat.calibration-good'),
        'calibrationOver' => __('frontend.chat.calibration-over'),
        'calibrationUnder' => __('frontend.chat.calibration-under'),
        'genericError' => __('frontend.chat.llm-error'),
        'connectionLost' => __('frontend.chat.connection-lost'),
        'stopped' => __('frontend.chat.stopped'),
        'copied' => __('frontend.chat.copied'),
        'copy' => __('frontend.chat.copy'),
    ];
@endphp

<x-frontend.layout :title="$conversation->displayTitle()" workspace shell>
    {{-- ===================================================================
         The descent — one subject.

         The most important screen in the product, so it is the calmest. The
         thread renders server-side; chat.js streams the live turn and swaps in
         exactly one control when it lands. Two columns on wide screens so the
         reading column keeps its measure regardless of viewport width — the rail
         reports, it never competes.
         =================================================================== --}}
    <div class="mx-auto grid w-full max-w-[78rem] grid-cols-1 gap-8 px-4 sm:px-6 lg:grid-cols-[minmax(0,1fr)_15rem] lg:px-8">

        {{-- ---- Centre: the workspace. --}}
        <div class="dth-workspace flex min-h-[calc(100svh-4rem)] flex-col">

            {{-- Objective header. Always discoverable, never in the way. --}}
            <header class="dth-peripheral sticky top-16 z-20 -mx-4 border-b border-border/60 bg-background/85 px-4 py-3 backdrop-blur-xl sm:-mx-6 sm:px-6">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="dth-coord">{{ __('frontend.chat.subject-label') }}</p>
                        {{-- The shared element the home composer transitions into. --}}
                        <h1 class="truncate font-display text-base font-semibold text-foreground"
                            style="view-transition-name: dth-subject">{{ $conversation->subject }}</h1>
                    </div>

                    <div class="flex shrink-0 items-center gap-2">
                        @if ($conversation->learningMode)
                            <span class="hidden items-center gap-1.5 rounded-full border border-border bg-surface/50 px-2.5 py-1 text-xs text-foreground-muted sm:inline-flex">
                                <span class="material-symbols-outlined text-[0.95rem]" aria-hidden="true">{{ $conversation->learningMode->icon }}</span>
                                {{ $conversation->learningMode->name }}
                            </span>
                        @endif

                        @if ($isOwner)
                            {{-- Publish read-only. One button, two states, each
                                 stating what it will do rather than what is true. --}}
                            <form method="POST" action="{{ route('subject.share', $conversation) }}">
                                @csrf
                                <button type="submit"
                                        title="{{ $conversation->isShared() ? __('frontend.subjects.unshare') : __('frontend.subjects.share') }}"
                                        @class([
                                            'grid h-9 w-9 place-items-center rounded-xl border transition duration-(--motion-feedback) focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring',
                                            'border-primary/45 bg-primary/10 text-primary' => $conversation->isShared(),
                                            'border-border text-foreground-muted hover:border-primary/40 hover:text-foreground' => ! $conversation->isShared(),
                                        ])>
                                    <span class="material-symbols-outlined text-[1.15rem]" aria-hidden="true">{{ $conversation->isShared() ? 'public' : 'ios_share' }}</span>
                                    <span class="sr-only">{{ $conversation->isShared() ? __('frontend.subjects.unshare') : __('frontend.subjects.share') }}</span>
                                </button>
                            </form>
                        @endif

                        {{-- Deep-work mode: collapses the periphery and concentrates
                             luminance on this column. Never blurs anything. --}}
                        <button type="button" data-focus-toggle aria-pressed="false"
                                class="grid h-9 w-9 place-items-center rounded-xl border border-border text-foreground-muted transition duration-(--motion-feedback) hover:border-primary/40 hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                title="{{ __('frontend.chat.focus-toggle') }}">
                            <span class="material-symbols-outlined text-[1.15rem]" aria-hidden="true">center_focus_strong</span>
                            <span class="sr-only">{{ __('frontend.chat.focus-toggle') }}</span>
                        </button>
                    </div>
                </div>

                {{-- Depth as coordinates, not a percentage bar with no meaning. --}}
                <div class="mt-2.5 flex items-center gap-3">
                    <div class="h-1 flex-1 overflow-hidden rounded-full bg-surface-muted" role="presentation">
                        <div class="h-full rounded-full bg-primary transition-[width] duration-(--motion-panel) ease-(--ease-out)"
                             style="width: {{ $progress }}%"></div>
                    </div>
                    <p class="dth-coord shrink-0">
                        {{ __('frontend.chat.depth-reached', [
                            'depth' => str_pad($conversation->current_depth, 2, '0', STR_PAD_LEFT),
                            'max' => str_pad($maxDepth, 2, '0', STR_PAD_LEFT),
                        ]) }}
                    </p>
                </div>

                {{-- What this descent is grounded in, when it is grounded in
                     something. The guide is instructed to teach from it. --}}
                @if ($source)
                    <a href="{{ $source->url }}" rel="noopener noreferrer nofollow" target="_blank"
                       class="mt-2.5 inline-flex max-w-full items-center gap-1.5 rounded-lg border border-border bg-surface/40 px-2.5 py-1 text-xs text-foreground-muted transition duration-(--motion-feedback) hover:border-primary/40 hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                        <span class="material-symbols-outlined shrink-0 text-[0.95rem]" aria-hidden="true">link</span>
                        <span class="truncate">{{ $source->displayTitle() }}</span>
                        <span class="dth-coord shrink-0">{{ number_format($source->words) }}w</span>
                    </a>
                @endif
            </header>

            {{-- Thread. Each turn is content-visibility:auto so a 40-turn subject
                 doesn't pay layout for what is off-screen. --}}
            <div id="dth-thread" class="flex flex-1 flex-col gap-8 py-8">
                @foreach ($messages as $message)
                    @php
                        $isUser = $message->role === Message::ROLE_USER;
                        $isSubject = $loop->first && $isUser;
                        $attempt = $attempts->get($message->id);
                    @endphp

                    @continue($isSubject)

                    <article class="dth-turn measure mx-auto w-full">
                        @if ($isUser)
                            {{-- The learner's proof. Visually distinct from teaching so
                                 the two are never confused when scrolling back. --}}
                            <div class="rounded-2xl border border-primary/25 bg-primary/8 px-4 py-3">
                                <p class="dth-coord mb-1.5">{{ __('frontend.chat.your-answer') }}</p>
                                <x-frontend.markdown :content="$message->content" class="text-[0.95rem]" />
                            </div>

                            @if ($attempt)
                                @include('frontend.chat.partials.verdict', ['attempt' => $attempt])
                            @endif
                        @elseif ($message->phase !== Message::PHASE_GRADE)
                            <div class="group/turn relative">
                                <x-frontend.markdown :content="$message->content" />
                                <button type="button" data-copy-turn
                                        title="{{ __('frontend.chat.copy') }}"
                                        class="dth-copy absolute -top-1 right-0 grid h-8 w-8 place-items-center rounded-lg border border-border bg-surface/80 text-foreground-muted backdrop-blur-sm transition duration-(--motion-feedback) hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                    <span class="material-symbols-outlined text-[1rem]" aria-hidden="true">content_copy</span>
                                    <span class="sr-only">{{ __('frontend.chat.copy') }}</span>
                                </button>
                            </div>
                        @endif
                    </article>
                @endforeach

                {{-- The guide, working. Real stages arrive over SSE and land here
                     in order; chat.js moves this block to the end of the thread at
                     the start of every turn, so it always sits directly above the
                     answer it belongs to. --}}
                <div id="dth-thinking" class="dth-thinking measure mx-auto w-full" hidden>
                    <details data-thinking-panel open
                             class="group rounded-2xl border border-border/70 bg-surface/40 backdrop-blur-sm">
                        <summary class="flex cursor-pointer list-none items-center gap-2.5 px-3.5 py-2.5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                            <span class="dth-descent-dot" aria-hidden="true"></span>
                            <span class="flex-1 text-sm text-foreground-muted" data-thinking-label>{{ __('frontend.chat.thinking') }}</span>
                            <span class="material-symbols-outlined text-[1.1rem] text-foreground-muted transition-transform duration-(--motion-state) group-open:rotate-180"
                                  aria-hidden="true">expand_more</span>
                        </summary>

                        <ol data-thinking-stages class="dth-stages px-3.5 pb-3.5 pt-0.5"></ol>
                    </details>
                </div>

                {{-- Streaming turns are appended here by chat.js. --}}
            </div>

            {{-- ---- Controls. chat.js reveals exactly one. --------------- --}}
            <div class="sticky bottom-0 z-20 -mx-4 border-t border-border/60 bg-background/90 px-4 pb-[max(1rem,env(safe-area-inset-bottom))] pt-4 backdrop-blur-xl sm:-mx-6 sm:px-6">
                <div class="measure mx-auto w-full">

                    {{-- New feedback opens above the composer. Its native summary
                         then keeps the outcome visible while the learner collapses
                         the detail; chat.js announces it through the layout region. --}}
                    <details id="dth-verdict" hidden open
                             class="group/verdict mb-4 rounded-2xl border border-border-strong bg-surface/70 p-4 backdrop-blur-md"></details>

                    {{-- Prove it. The form posts for real without JS; chat.js
                         intercepts the submit to grade in place instead. --}}
                    <div id="dth-control-checkpoint" class="dth-checkpoint relative" hidden>
                        <form id="dth-proof-form" method="POST" action="{{ route('subject.checkpoint', $conversation) }}">
                            @csrf
                            <label for="dth-proof" class="mb-2 flex items-center gap-1.5 font-mono text-xs uppercase tracking-[0.16em] text-primary">
                                <span class="material-symbols-outlined text-[1rem]" aria-hidden="true">quiz</span>
                                {{ __('frontend.chat.checkpoint') }}
                            </label>

                            <div class="dth-composer rounded-2xl border border-border-strong bg-surface/70 p-2 shadow-xl backdrop-blur-md">
                                <textarea id="dth-proof" name="message" rows="2" required minlength="2" maxlength="4000"
                                          data-autogrow data-submit-shortcut
                                          aria-keyshortcuts="Meta+Enter Control+Enter Alt+Enter"
                                          aria-describedby="dth-proof-shortcut"
                                          placeholder="{{ __('frontend.chat.proof-placeholder') }}"
                                          class="dth-autogrow block w-full resize-none border-0 bg-transparent px-3 py-2 text-base text-foreground placeholder:text-foreground-muted/70 focus:outline-none"></textarea>

                                <x-frontend.submit-shortcut id="dth-proof-shortcut" class="px-3 pt-1" />

                                <div class="flex flex-col gap-3 px-1 pb-1 pt-2 sm:flex-row sm:items-end sm:justify-between">
                                    {{-- Optional self-rating before submitting. Calibration
                                         is a skill; you can only improve it if you can see
                                         the gap between felt and shown understanding. --}}
                                    <fieldset class="min-w-0">
                                        <legend class="dth-coord mb-1.5">{{ __('frontend.chat.confidence-legend') }}</legend>
                                        <div class="flex flex-wrap gap-1.5">
                                            @foreach ([25 => 'shaky', 60 => 'mostly', 90 => 'solid'] as $value => $key)
                                                <label class="cursor-pointer">
                                                    <input type="radio" name="self_rating" value="{{ $value }}" class="peer sr-only">
                                                    <span class="inline-block rounded-full border border-border px-2.5 py-1 text-xs text-foreground-muted transition duration-(--motion-instant) peer-checked:border-primary/60 peer-checked:bg-primary/10 peer-checked:text-primary peer-focus-visible:ring-2 peer-focus-visible:ring-ring">
                                                        {{ __('frontend.chat.confidence.'.$key) }}
                                                    </span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </fieldset>

                                    <button type="submit"
                                            class="group/cta inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground transition duration-(--motion-feedback) ease-(--ease-snap) hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:cursor-wait disabled:opacity-70">
                                        {{ __('frontend.chat.submit-proof') }}
                                        <span class="dth-cta-arrow material-symbols-outlined text-[1.1rem]" aria-hidden="true">arrow_downward</span>
                                    </button>
                                </div>
                            </div>
                        </form>

                        {{-- Progressive disclosure: stuck? These re-teach the SAME
                             layer from a different angle. Nothing here gives the
                             answer away, and nothing skips the checkpoint. --}}
                        <details class="group/help mt-3">
                            <summary class="inline-flex cursor-pointer list-none items-center gap-1.5 rounded-lg px-1 py-1 text-xs text-foreground-muted transition hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                <span class="material-symbols-outlined text-[1rem]" aria-hidden="true">help</span>
                                {{ __('frontend.chat.stuck') }}
                                <span class="material-symbols-outlined text-[1rem] transition-transform duration-(--motion-state) group-open/help:rotate-180" aria-hidden="true">expand_more</span>
                            </summary>
                            <div class="mt-2 flex flex-wrap gap-2">
                                @foreach (['different' => 'flip_camera_android', 'analogy' => 'lightbulb', 'evidence' => 'link', 'challenge' => 'bolt'] as $reframe => $icon)
                                    <button type="button" data-reframe="{{ $reframe }}"
                                            class="inline-flex items-center gap-1.5 rounded-full border border-border bg-surface/50 px-3 py-1.5 text-xs text-foreground-muted transition duration-(--motion-feedback) hover:border-primary/45 hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                        <span class="material-symbols-outlined text-[0.95rem]" aria-hidden="true">{{ $icon }}</span>
                                        {{ __('frontend.chat.reframe.'.$reframe) }}
                                    </button>
                                @endforeach
                            </div>
                        </details>
                    </div>

                    {{-- Go deeper --}}
                    <div id="dth-control-deeper" hidden>
                        <div class="flex flex-col items-center gap-3 text-center">
                            <button type="button" data-descend-deeper
                                    class="group/cta inline-flex items-center gap-2 rounded-xl bg-primary px-6 py-3 text-sm font-semibold text-primary-foreground transition duration-(--motion-feedback) ease-(--ease-snap) hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                {{ $conversation->current_depth === 0 ? __('frontend.chat.begin') : __('frontend.chat.go-deeper') }}
                                <span class="dth-cta-arrow material-symbols-outlined text-[1.2rem]" aria-hidden="true">south_east</span>
                            </button>
                            <p class="dth-coord">{{ __('frontend.chat.next-layer', ['depth' => $conversation->current_depth]) }}</p>
                        </div>
                    </div>

                    {{-- Surfaced — the end of the descent. The reward is the
                         record of what was learned, not a trophy animation. --}}
                    <div id="dth-control-surfaced" hidden>
                        <div class="flex flex-col items-center gap-2 text-center">
                            <span class="material-symbols-outlined text-[1.6rem] text-success" aria-hidden="true">workspace_premium</span>
                            <h2 class="font-display text-lg font-semibold text-foreground">{{ __('frontend.chat.surfaced-title') }}</h2>
                            <p class="max-w-md text-sm text-foreground-muted">{{ __('frontend.chat.surfaced-body') }}</p>

                            @if ($concepts->isNotEmpty())
                                <div class="mt-3 flex flex-wrap justify-center gap-1.5">
                                    @foreach ($concepts as $concept)
                                        <x-frontend.concept-chip :concept="$concept" :current-depth="$conversation->current_depth" />
                                    @endforeach
                                </div>
                            @endif

                            <div class="mt-4 flex flex-wrap items-center justify-center gap-2">
                                <a href="{{ route('home') }}"
                                   class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                    {{ __('frontend.chat.new-descent') }}
                                </a>
                                @auth
                                    <a href="{{ route('subjects.index') }}"
                                       class="inline-flex items-center gap-2 rounded-xl border border-border-strong px-5 py-2.5 text-sm font-medium text-foreground transition hover:border-primary/50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                        {{ __('frontend.sidebar.all') }}
                                    </a>
                                @endauth
                            </div>
                        </div>
                    </div>

                    {{-- Streaming: the one control that matters mid-turn is the
                         way to stop it. The partial layer is still persisted. --}}
                    <div id="dth-control-stop" hidden class="flex justify-center">
                        <button type="button" data-stop-stream
                                class="inline-flex items-center gap-2 rounded-xl border border-border-strong px-4 py-2 text-sm text-foreground-muted transition duration-(--motion-feedback) hover:border-danger/50 hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                            <span class="material-symbols-outlined text-[1.1rem]" aria-hidden="true">stop_circle</span>
                            {{ __('frontend.chat.stop') }}
                        </button>
                    </div>

                    <p id="dth-error" hidden role="alert"
                       class="rounded-xl border border-danger/40 bg-danger/10 px-4 py-2.5 text-center text-sm text-danger"></p>
                </div>
            </div>
        </div>

        {{-- ---- Right rail: where you are, and what you know. Collapses under
                  the thread on mobile. --}}
        <aside class="dth-peripheral pb-10 lg:pb-0">
            <div class="sticky top-28 space-y-7 pt-6">
                <x-frontend.depth-rail :conversation="$conversation" :concepts="$concepts" :mastery="$mastery" />

                <div aria-labelledby="dth-concepts-heading">
                    <h2 id="dth-concepts-heading" class="dth-coord mb-3">{{ __('frontend.chat.concepts') }}</h2>

                    @if ($concepts->isEmpty())
                        <p class="text-xs leading-relaxed text-foreground-muted/80">{{ __('frontend.chat.concepts-empty') }}</p>
                    @else
                        <div class="flex flex-wrap gap-1.5">
                            @foreach ($concepts as $concept)
                                <x-frontend.concept-chip :concept="$concept" :current-depth="$conversation->current_depth" />
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </aside>
    </div>

    {{-- Config for chat.js. --}}
    <div data-chat hidden
         data-stream-url="{{ route('subject.stream', $conversation) }}"
         data-checkpoint-url="{{ route('subject.checkpoint', $conversation) }}"
         data-status="{{ $conversation->status }}"
         data-depth="{{ $conversation->current_depth }}"
         data-max-depth="{{ $maxDepth }}"
         data-autostream="{{ $autostream ? '1' : '0' }}"
         data-strings="{{ json_encode($strings) }}"></div>

    @push('scripts')
        <script src="{{ loadFiles('js/frontend/markdown.js') }}" defer></script>
        <script src="{{ loadFiles('js/frontend/chat.js') }}" defer></script>
    @endpush
</x-frontend.layout>
