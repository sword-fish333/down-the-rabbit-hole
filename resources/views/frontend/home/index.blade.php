<x-frontend.layout>
    {{-- ===================================================================
         Home — the front door. Ungated entry: name a subject and fall in.
         This composer is the activation "aha"; the chat thread it opens is
         the next build. Form action is a placeholder until the chat route
         (POST /descend → ChatController) exists.
         =================================================================== --}}
    <section class="relative mx-auto flex min-h-[calc(100vh-4rem)] max-w-3xl flex-col items-center justify-center px-4 py-16 text-center sm:px-6">

        {{-- Kicker --}}
        <span class="mb-6 inline-flex items-center gap-2 rounded-full border border-primary/30 bg-primary/8 px-3.5 py-1 font-mono text-xs uppercase tracking-[0.18em] text-primary">
            <span class="material-symbols-outlined text-[1rem]">arrow_downward</span>
            {{ __('frontend.home.kicker') }}
        </span>

        {{-- Headline --}}
        <h1 class="text-balance font-display text-4xl font-semibold leading-[1.05] tracking-tight text-foreground sm:text-5xl md:text-6xl">
            {{ __('frontend.home.title') }}
        </h1>
        <p class="mt-5 max-w-xl text-pretty text-base text-foreground-muted sm:text-lg">
            {{ __('frontend.home.subtitle') }}
        </p>

        {{-- The composer — the ungated entry point. --}}
        <form action="#" method="POST" class="group mt-10 w-full" data-descend>
            @csrf
            <div class="relative rounded-2xl border border-border-strong bg-surface/70 p-2 shadow-xl backdrop-blur-md transition focus-within:border-primary/60 focus-within:ring-2 focus-within:ring-ring/40 dth-glow-cyan">
                <label for="dth-prompt" class="sr-only">{{ __('frontend.home.title') }}</label>
                <textarea
                    id="dth-prompt"
                    name="prompt"
                    rows="2"
                    required
                    placeholder="{{ __('frontend.home.placeholder') }}"
                    class="block w-full resize-none border-0 bg-transparent px-4 py-3 text-base text-foreground placeholder:text-foreground-muted/70 focus:outline-none"></textarea>
                <div class="flex items-center justify-between gap-3 px-2 pb-1 pt-2">
                    <span class="flex items-center gap-1.5 font-mono text-xs text-foreground-muted/70">
                        <span class="material-symbols-outlined text-[1rem] text-success">neurology</span>
                        deep-work mode
                    </span>
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                        {{ __('frontend.home.cta') }}
                        <span class="material-symbols-outlined text-[1.15rem]">south_east</span>
                    </button>
                </div>
            </div>
        </form>

        {{-- One-tap rabbit holes — lower the friction to start. --}}
        <div class="mt-7 flex flex-col items-center gap-3">
            <span class="font-mono text-xs uppercase tracking-[0.16em] text-foreground-muted/70">
                {{ __('frontend.home.topics-label') }}
            </span>
            <div class="flex flex-wrap items-center justify-center gap-2">
                @foreach (__('frontend.home.topics') as $topic)
                    <button type="button" data-topic="{{ $topic }}"
                            class="rounded-full border border-border bg-surface/50 px-3.5 py-1.5 text-sm text-foreground-muted transition hover:border-primary/50 hover:text-foreground hover:bg-primary/8">
                        {{ $topic }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Quiet promise — three points, no hard sell. --}}
        <div class="mt-16 grid w-full grid-cols-1 gap-4 sm:grid-cols-3">
            @php($feats = [
                ['icon' => 'flowsheet',     'key' => 'flow'],
                ['icon' => 'quiz',          'key' => 'prove'],
                ['icon' => 'auto_stories',  'key' => 'real'],
            ])
            @foreach ($feats as $f)
                <div class="rounded-2xl border border-border/70 bg-surface/40 p-5 text-left backdrop-blur-sm">
                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-primary/10 text-primary ring-1 ring-primary/20">
                        <span class="material-symbols-outlined text-[1.25rem]">{{ $f['icon'] }}</span>
                    </span>
                    <h3 class="mt-4 font-display text-sm font-semibold text-foreground">
                        {{ __('frontend.home.features.'.$f['key'].'.title') }}
                    </h3>
                    <p class="mt-1.5 text-sm leading-relaxed text-foreground-muted">
                        {{ __('frontend.home.features.'.$f['key'].'.body') }}
                    </p>
                </div>
            @endforeach
        </div>
    </section>
</x-frontend.layout>
