@php
    use App\Models\CheckpointAttempt;
    use App\Models\Message;

    $maxDepth = (int) config('platform.chat.max_depth');
    $autostream = session('stream') && ! $conversation->isSurfaced();
    $progress = $maxDepth > 0 ? min(100, round($conversation->current_depth / $maxDepth * 100)) : 0;

    // Strings chat.js needs. Passed as one JSON blob rather than a dozen data-*
    // attributes, so the localisation stays in lang/ and out of the JS.
    $strings = [
        'thinking' => __('frontend.chat.thinking'),
        'analyzing' => __('frontend.chat.analyzing'),
        'checkpointReady' => __('frontend.chat.checkpoint-ready'),
        'surfaced' => __('frontend.chat.surfaced-title'),
        'yourAnswer' => __('frontend.chat.your-answer'),
        'verdictPass' => __('frontend.chat.verdict-pass'),
        'verdictIncomplete' => __('frontend.chat.verdict-incomplete'),
        'verdictMisconception' => __('frontend.chat.verdict-misconception'),
        'scoreLabel' => __('frontend.chat.score'),
        'youThought' => __('frontend.chat.you-thought'),
        'calibrationGood' => __('frontend.chat.calibration-good'),
        'calibrationOver' => __('frontend.chat.calibration-over'),
        'calibrationUnder' => __('frontend.chat.calibration-under'),
        'genericError' => __('frontend.chat.llm-error'),
        'connectionLost' => __('frontend.chat.connection-lost'),
    ];
@endphp

<x-frontend.layout :title="$conversation->displayTitle()" workspace>
    {{-- ===================================================================
         The descent — one rabbit hole.

         The most important screen in the product, so it is the calmest. The
         thread renders server-side; chat.js streams the live turn and swaps in
         exactly one control when it lands. Layout is a three-column grid on
         wide screens so the reading column keeps its measure regardless of
         viewport width — the rail does not steal from it.
         =================================================================== --}}
    <div class="mx-auto grid w-full max-w-[92rem] grid-cols-1 gap-8 px-4 sm:px-6 lg:grid-cols-[11rem_minmax(0,1fr)_13rem] lg:px-8">

        {{-- ---- Left rail: where you are. Sticky, quiet, collapses away on mobile. --}}
        <div class="hidden lg:block">
            <div class="sticky top-24 pt-6">
                <x-frontend.depth-rail :conversation="$conversation" :concepts="$concepts" :mastery="$mastery" />
            </div>
        </div>

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
                        {{ __('frontend.chat.depth') }} {{ str_pad($conversation->current_depth, 2, '0', STR_PAD_LEFT) }}
                        <span aria-hidden="true">/</span>
                        <span class="sr-only">{{ __('frontend.chat.of') }}</span>
                        {{ str_pad($maxDepth, 2, '0', STR_PAD_LEFT) }}
                    </p>
                </div>
            </header>

            {{-- Thread. Each turn is content-visibility:auto so a 40-turn hole
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
                            <x-frontend.markdown :content="$message->content" />
                        @endif
                    </article>
                @endforeach

                {{-- Streaming turns are appended here by chat.js. --}}
            </div>

            {{-- ---- Controls. chat.js reveals exactly one. --------------- --}}
            <div class="sticky bottom-0 z-20 -mx-4 border-t border-border/60 bg-background/90 px-4 pb-[max(1rem,env(safe-area-inset-bottom))] pt-4 backdrop-blur-xl sm:-mx-6 sm:px-6">
                <div class="measure mx-auto w-full">

                    {{-- The graded verdict lands here first, above the composer. --}}
                    <div id="dth-verdict" hidden
                         class="mb-4 rounded-2xl border border-border-strong bg-surface/70 p-4 backdrop-blur-md"
                         role="status" aria-live="polite"></div>

                    {{-- Prove it. The form posts for real without JS; chat.js
                         intercepts the submit to grade in place instead. --}}
                    <div id="dth-control-checkpoint" class="dth-checkpoint relative" hidden>
                        <form id="dth-proof-form" method="POST" action="{{ route('hole.checkpoint', $conversation) }}">
                            @csrf
                            <label for="dth-proof" class="mb-2 flex items-center gap-1.5 font-mono text-xs uppercase tracking-[0.16em] text-primary">
                                <span class="material-symbols-outlined text-[1rem]" aria-hidden="true">quiz</span>
                                {{ __('frontend.chat.checkpoint') }}
                            </label>

                            <div class="dth-composer rounded-2xl border border-border-strong bg-surface/70 p-2 shadow-xl backdrop-blur-md">
                                <textarea id="dth-proof" name="message" rows="3" required minlength="2" maxlength="4000"
                                          data-autogrow data-submit-on-enter
                                          placeholder="{{ __('frontend.chat.proof-placeholder') }}"
                                          class="block w-full resize-none border-0 bg-transparent px-3 py-2 text-base text-foreground placeholder:text-foreground-muted/70 focus:outline-none"></textarea>

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
                                    <a href="{{ route('holes.index') }}"
                                       class="inline-flex items-center gap-2 rounded-xl border border-border-strong px-5 py-2.5 text-sm font-medium text-foreground transition hover:border-primary/50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                        {{ __('frontend.chat.all-holes') }}
                                    </a>
                                @endauth
                            </div>
                        </div>
                    </div>

                    {{-- Waiting on the guide. One line, no bouncing dots. --}}
                    <p id="dth-thinking" hidden
                       class="flex items-center justify-center gap-2 text-center font-mono text-xs text-foreground-muted/70">
                        <span class="material-symbols-outlined text-[1rem] text-primary" aria-hidden="true">neurology</span>
                        {{ __('frontend.chat.thinking') }}
                    </p>

                    <p id="dth-error" hidden role="alert"
                       class="rounded-xl border border-danger/40 bg-danger/10 px-4 py-2.5 text-center text-sm text-danger"></p>
                </div>
            </div>
        </div>

        {{-- ---- Right rail: what you know. Collapses under the thread on mobile. --}}
        <aside class="dth-peripheral pb-10 lg:pb-0" aria-labelledby="dth-concepts-heading">
            <div class="sticky top-24 pt-6">
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
        </aside>
    </div>

    {{-- Config for chat.js. --}}
    <div data-chat hidden
         data-stream-url="{{ route('hole.stream', $conversation) }}"
         data-checkpoint-url="{{ route('hole.checkpoint', $conversation) }}"
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
