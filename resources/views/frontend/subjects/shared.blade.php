@php
    use App\Models\Message;

    $maxDepth = (int) config('platform.chat.max_depth');
    $source = $conversation->sources->first();
@endphp

<x-frontend.layout :title="$conversation->displayTitle()"
                   :description="__('frontend.subjects.shared-meta', ['subject' => $conversation->subject])">
    {{-- ===================================================================
         A shared subject — public, read-only.

         The transcript and what was proven, nothing that could advance anyone
         else's descent. It is server-rendered like every other page here, so it
         is crawlable and shareable without a second rendering path.
         =================================================================== --}}
    <article class="mx-auto w-full max-w-3xl px-4 py-10 sm:px-6 lg:px-8">

        <header class="border-b border-border/60 pb-6">
            <p class="dth-coord">{{ __('frontend.chat.subject-label') }}</p>
            <h1 class="mt-1.5 text-balance font-display text-2xl font-semibold tracking-tight text-foreground sm:text-3xl">
                {{ $conversation->subject }}
            </h1>

            <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-foreground-muted">
                <span class="dth-coord">
                    {{ __('frontend.chat.depth-reached', [
                        'depth' => str_pad($conversation->current_depth, 2, '0', STR_PAD_LEFT),
                        'max' => str_pad($maxDepth, 2, '0', STR_PAD_LEFT),
                    ]) }}
                </span>

                @if ($conversation->learningMode)
                    <span class="inline-flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[0.95rem]" aria-hidden="true">{{ $conversation->learningMode->icon }}</span>
                        {{ $conversation->learningMode->label('name') }}
                    </span>
                @endif

                @if ($mastery['mastered'])
                    <span class="inline-flex items-center gap-1.5 text-success">
                        <span class="material-symbols-outlined text-[0.95rem]" aria-hidden="true">verified</span>
                        {{ trans_choice('frontend.subjects.mastered', $mastery['mastered'], ['count' => $mastery['mastered']]) }}
                    </span>
                @endif

                @if ($conversation->user)
                    <span class="ml-auto">{{ __('frontend.subjects.shared-by', ['name' => $conversation->user->fullName()]) }}</span>
                @endif
            </div>

            @if ($source)
                <a href="{{ $source->url }}" rel="noopener noreferrer nofollow" target="_blank"
                   class="mt-4 inline-flex max-w-full items-center gap-2 rounded-xl border border-border bg-surface/40 px-3 py-2 text-xs text-foreground-muted transition hover:border-primary/40 hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                    <span class="material-symbols-outlined shrink-0 text-[1rem]" aria-hidden="true">link</span>
                    <span class="truncate">{{ $source->displayTitle() }}</span>
                </a>
            @endif
        </header>

        <div class="flex flex-col gap-8 py-8">
            @foreach ($messages as $message)
                @php($isUser = $message->role === Message::ROLE_USER)

                @continue($loop->first && $isUser)
                @continue($message->phase === Message::PHASE_GRADE)

                <div class="dth-turn measure mx-auto w-full">
                    @if ($isUser)
                        <div class="rounded-2xl border border-primary/25 bg-primary/8 px-4 py-3">
                            <p class="dth-coord mb-1.5">{{ __('frontend.chat.their-answer') }}</p>
                            <x-frontend.markdown :content="$message->content" class="text-[0.95rem]" />
                        </div>
                    @else
                        @if ($message->phase === Message::PHASE_QUESTION)
                            <p class="dth-coord mb-3 flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-[1rem] text-primary" aria-hidden="true">psychology_alt</span>
                                {{ __('frontend.approach.posed') }}
                            </p>
                        @endif

                        <x-frontend.markdown :content="$message->content" />
                    @endif
                </div>
            @endforeach
        </div>

        @if ($concepts->isNotEmpty())
            <section class="border-t border-border/60 pt-6" aria-labelledby="dth-shared-concepts">
                <h2 id="dth-shared-concepts" class="dth-coord mb-3">{{ __('frontend.chat.concepts') }}</h2>
                <div class="flex flex-wrap gap-1.5">
                    @foreach ($concepts as $concept)
                        <x-frontend.concept-chip :concept="$concept" :current-depth="$conversation->current_depth" />
                    @endforeach
                </div>
            </section>
        @endif

        {{-- The only call to action: go and prove it yourself. --}}
        <div class="mt-10 rounded-2xl border border-primary/25 bg-primary/8 p-6 text-center">
            <p class="font-display text-lg font-semibold text-foreground">{{ __('frontend.subjects.shared-cta-title') }}</p>
            <p class="mx-auto mt-1.5 max-w-md text-sm text-foreground-muted">{{ __('frontend.subjects.shared-cta-body') }}</p>
            <a href="{{ route('home') }}"
               class="group/cta mt-4 inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground transition duration-(--motion-feedback) ease-(--ease-snap) hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                {{ __('frontend.home.cta') }}
                <span class="dth-cta-arrow material-symbols-outlined text-[1.1rem]" aria-hidden="true">south_east</span>
            </a>
        </div>
    </article>
</x-frontend.layout>
