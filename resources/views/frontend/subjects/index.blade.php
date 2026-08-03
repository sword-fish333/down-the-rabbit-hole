<x-frontend.layout :title="__('frontend.subjects.title')" shell>
    {{-- ===================================================================
         The library — every subject, newest first.

         Ordered by what actually correlates with learning something: resume
         first, review second, start third. Search and the filters are plain
         GET, so this page works with JavaScript off; JS only upgrades them to
         swap rows in place and to append the next page on scroll.
         =================================================================== --}}
    <section class="mx-auto w-full max-w-4xl px-4 py-8 sm:px-6 lg:px-8"
             data-subject-library
             data-endpoint="{{ route('subjects.index') }}">

        <x-frontend.verify-banner />

        <div class="flex flex-wrap items-center gap-3">
            <h1 class="font-display text-2xl font-semibold tracking-tight text-foreground">
                {{ __('frontend.subjects.title') }}
            </h1>

            <div class="ml-auto flex flex-wrap items-center gap-2">
                {{-- Select mode. Revealed by JS because bulk selection is a
                     pointer affordance; deletion itself is a real form post. --}}
                <button type="button" data-select-toggle hidden
                        class="inline-flex items-center gap-1.5 rounded-xl border border-border px-3 py-2 text-sm text-foreground-muted transition duration-(--motion-feedback) hover:border-primary/40 hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                    <span class="material-symbols-outlined text-[1.1rem]" aria-hidden="true">check_box</span>
                    {{ __('frontend.subjects.select') }}
                </button>

                <a href="{{ route('home') }}"
                   class="group/cta inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground transition duration-(--motion-feedback) ease-(--ease-snap) hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                    {{ __('frontend.subjects.new') }}
                    <span class="dth-cta-arrow material-symbols-outlined text-[1.05rem]" aria-hidden="true">south_east</span>
                </a>
            </div>
        </div>

        {{-- Search + filters. One row on desktop, stacked on a phone. --}}
        <form method="GET" action="{{ route('subjects.index') }}" data-subject-search
              class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center">
            <label class="dth-composer flex min-w-0 flex-1 items-center gap-2 rounded-xl border border-border-strong bg-surface/60 px-3 py-2">
                <span class="material-symbols-outlined text-[1.15rem] text-foreground-muted" aria-hidden="true">search</span>
                <span class="sr-only">{{ __('frontend.subjects.search-label') }}</span>
                <input type="search" name="q" value="{{ $search }}" maxlength="120"
                       autocomplete="off" enterkeyhint="search"
                       placeholder="{{ __('frontend.subjects.search-placeholder') }}"
                       class="min-w-0 flex-1 border-0 bg-transparent text-sm text-foreground placeholder:text-foreground-muted/70 focus:outline-none">
                <input type="hidden" name="filter" value="{{ $filter }}">
            </label>

            <div class="flex flex-wrap items-center gap-1.5" role="group" aria-label="{{ __('frontend.subjects.filter-label') }}">
                @foreach (\App\Models\Conversation::FILTERS as $option)
                    <a href="{{ route('subjects.index', array_filter(['filter' => $option, 'q' => $search])) }}"
                       @if ($option === $filter) aria-current="true" @endif
                       @class([
                           'rounded-full border px-3 py-1.5 text-xs transition duration-(--motion-feedback) ease-(--ease-snap) focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring',
                           'border-primary/45 bg-primary/10 text-primary' => $option === $filter,
                           'border-border text-foreground-muted hover:border-primary/35 hover:text-foreground' => $option !== $filter,
                       ])>
                        {{ __('frontend.subjects.filter.'.$option) }}
                    </a>
                @endforeach
            </div>
        </form>

        {{-- Selection bar. Sticky, so a long selection never scrolls its own
             actions off screen. --}}
        <form id="dth-subject-selection" method="POST" action="{{ route('subjects.destroy') }}"
              data-selection-bar hidden
              class="sticky top-[4.25rem] z-20 mt-4 flex flex-wrap items-center gap-2 rounded-xl border border-border-strong bg-surface/90 px-3 py-2 backdrop-blur-md">
            @csrf
            @method('DELETE')

            <p class="text-sm text-foreground-muted" aria-live="polite">
                <span data-selection-count>0</span> {{ __('frontend.subjects.selected') }}
            </p>

            <div class="ml-auto flex items-center gap-1.5">
                <button type="button" data-select-all
                        class="rounded-lg px-2.5 py-1.5 text-xs text-foreground-muted transition hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                    {{ __('frontend.subjects.select-all') }}
                </button>
                <button type="submit" data-selection-submit disabled
                        class="inline-flex items-center gap-1.5 rounded-lg border border-danger/40 bg-danger/10 px-2.5 py-1.5 text-xs font-medium text-danger transition hover:bg-danger/15 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:opacity-40">
                    <span class="material-symbols-outlined text-[1rem]" aria-hidden="true">delete</span>
                    {{ __('frontend.subjects.delete') }}
                </button>
                <button type="button" data-select-cancel
                        class="rounded-lg px-2.5 py-1.5 text-xs text-foreground-muted transition hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                    {{ __('frontend.general.cancel') }}
                </button>
            </div>
        </form>

        <ul id="dth-subject-rows" class="mt-4 border-t border-border/50" data-rows>
            @include('frontend.subjects.partials.rows')
        </ul>

        {{-- Infinite scroll sentinel + the no-JS way to the next page. --}}
        <div class="py-6 text-center" data-rows-more>
            @if ($subjects->hasMorePages())
                <a href="{{ $subjects->nextPageUrl() }}" data-rows-next
                   class="inline-flex items-center gap-2 rounded-xl border border-border px-4 py-2 text-sm text-foreground-muted transition hover:border-primary/40 hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                    <span class="material-symbols-outlined text-[1.1rem]" aria-hidden="true">expand_more</span>
                    {{ __('frontend.subjects.load-more') }}
                </a>
            @endif
        </div>
    </section>

    @push('scripts')
        <script src="{{ loadFiles('js/frontend/subjects.js') }}" defer></script>
    @endpush
</x-frontend.layout>
