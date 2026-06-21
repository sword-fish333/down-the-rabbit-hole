{{-- Frontend top bar. Translucent noir glass; brand left, controls right. --}}
<header class="sticky top-0 z-50 border-b border-border/70 bg-background/72 backdrop-blur-xl">
    <nav class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
        {{-- Brand --}}
        <a href="{{ route('home') }}"
           class="group flex items-center gap-2.5 font-display text-base font-semibold tracking-tight text-foreground">
            <span class="grid h-9 w-9 place-items-center rounded-xl bg-primary/12 text-primary ring-1 ring-primary/25 transition group-hover:bg-primary/20">
                <span class="material-symbols-outlined is-filled text-[1.25rem]">arrow_downward</span>
            </span>
            <span>{{ config('app.name') }}</span>
        </a>

        {{-- Controls --}}
        <div class="flex items-center gap-2 sm:gap-3">
            <x-frontend.theme-switch class="shrink-0" />

            <a href="#"
               class="hidden rounded-xl px-3.5 py-2 text-sm font-medium text-foreground-muted transition hover:text-foreground sm:inline-flex">
                {{ __('frontend.navbar.sign-in') }}
            </a>

            <a href="#"
               class="inline-flex items-center gap-1.5 rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                {{ __('frontend.navbar.start') }}
                <span class="material-symbols-outlined text-[1.05rem]">south_east</span>
            </a>
        </div>
    </nav>
</header>
