<x-frontend.layout :title="__('frontend.methods.items.'.$slug.'.name')"
                   :description="__('frontend.methods.entry-meta', ['name' => __('frontend.methods.items.'.$slug.'.name')])"
                   shell marketing>
    {{-- ===================================================================
         One method.

         Two halves with two different authors, and the page says so out loud:
         the entry is written by the guide in the reader's language, the reading
         list underneath is hand-checked and never generated. That separation is
         the only reason a page like this is worth publishing — see
         App\Services\Learning\MethodLibrary.

         One column at a reading measure at every width. There is nothing to put
         in a second column that would not be a distraction from a page whose
         whole job is to be read.
         =================================================================== --}}
    <article class="mx-auto w-full max-w-[46rem] px-4 py-10 sm:px-6 sm:py-14 lg:px-8">

        <a href="{{ route('methods.index') }}"
           class="inline-flex items-center gap-1.5 text-sm text-foreground-muted transition duration-(--motion-feedback) hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
            <span class="material-symbols-outlined text-[1.1rem]" aria-hidden="true">arrow_back</span>
            {{ __('frontend.methods.back') }}
        </a>

        <header class="mt-6">
            <span class="grid h-11 w-11 place-items-center rounded-2xl border border-primary/30 bg-primary/10">
                <span class="material-symbols-outlined text-[1.4rem] text-primary" aria-hidden="true">{{ $method['icon'] }}</span>
            </span>

            <h1 class="mt-4 text-balance font-display text-3xl font-semibold tracking-tight text-foreground sm:text-4xl">
                {{ __('frontend.methods.items.'.$slug.'.name') }}
            </h1>
            <p class="mt-3 text-pretty text-base leading-relaxed text-foreground-muted">
                {{ __('frontend.methods.items.'.$slug.'.summary') }}
            </p>
        </header>

        <div class="mt-9 border-t border-border/60 pt-9">
            @if ($entry)
                <x-frontend.markdown :content="$entry" />
            @else
                {{-- The model was unreachable. Everything on this page that did
                     not come from a model is still here, which is most of it. --}}
                <p class="rounded-2xl border border-border/70 bg-surface/40 px-4 py-3 text-sm text-foreground-muted">
                    {{ __('frontend.methods.unavailable') }}
                </p>
            @endif
        </div>

        {{-- The half nobody generated. --}}
        <section class="mt-10 rounded-2xl border border-border/70 bg-surface/40 p-5 backdrop-blur-sm sm:p-6"
                 aria-labelledby="dth-refs-heading">
            <h2 id="dth-refs-heading" class="dth-coord flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[1rem] text-primary" aria-hidden="true">library_books</span>
                {{ __('frontend.methods.references') }}
            </h2>

            <ul class="mt-4 space-y-3.5">
                @foreach ($method['references'] as $reference)
                    <li class="text-sm leading-relaxed text-foreground-muted">
                        {{ $reference['text'] }}
                        @if (! empty($reference['url']))
                            <a href="{{ $reference['url'] }}" rel="noopener noreferrer" target="_blank"
                               class="ml-1 inline-flex items-center gap-1 whitespace-nowrap text-primary underline decoration-primary/40 underline-offset-4 transition hover:decoration-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                {{ parse_url($reference['url'], PHP_URL_HOST) }}
                                <span class="material-symbols-outlined text-[0.9rem]" aria-hidden="true">open_in_new</span>
                            </a>
                        @endif
                    </li>
                @endforeach
            </ul>

            <p class="mt-5 border-t border-border/50 pt-4 text-xs leading-relaxed text-foreground-muted/80">
                {{ __('frontend.methods.references-note') }}
            </p>
        </section>

        {{-- Sideways, not onwards: the other entries, as chips rather than a
             second grid, so the page still ends on the one thing worth doing. --}}
        <nav class="mt-8 flex flex-wrap gap-1.5" aria-label="{{ __('frontend.methods.back') }}">
            @foreach (array_keys($methods) as $other)
                @continue($other === $slug)

                <a href="{{ route('methods.show', $other) }}"
                   class="inline-flex items-center rounded-full border border-border bg-surface/40 px-3 py-1.5 text-xs text-foreground-muted transition duration-(--motion-feedback) hover:border-primary/45 hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                    {{ __('frontend.methods.items.'.$other.'.name') }}
                </a>
            @endforeach
        </nav>

        <x-frontend.method-cta class="mt-10" />
    </article>
</x-frontend.layout>
