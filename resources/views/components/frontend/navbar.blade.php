{{-- Frontend top bar. Translucent noir glass; brand left, controls right. --}}
<header class="sticky top-0 z-50 border-b border-border/70 bg-background/72 backdrop-blur-xl">
    <nav class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
        {{-- Brand --}}
        <a href="{{ route('home') }}"
           class="group flex items-center gap-2.5 font-display text-base font-semibold tracking-tight text-foreground">
            <img src="{{ loadFiles('images/logos/main_logo.png') }}"
                 alt="{{ config('app.name') }}"
                 width="36" height="36" loading="eager" decoding="async"
                 class="dth-logo h-9 w-9 shrink-0 rounded-full object-contain ring-1 ring-border/60">

            <span>{{ config('app.name') }}</span>
        </a>

        {{-- Controls --}}
        <div class="flex items-center gap-2 sm:gap-3">
            <x-frontend.theme-switch class="shrink-0" />

            @auth
                <span class="flex items-center gap-2">
                    @if (auth()->user()->profileImageUrl())
                        <img src="{{ auth()->user()->profileImageUrl() }}" alt="" referrerpolicy="no-referrer"
                             class="h-8 w-8 rounded-full object-cover ring-1 ring-border/60">
                    @else
                        <span class="grid h-8 w-8 place-items-center rounded-full bg-primary/12 text-xs font-semibold text-primary ring-1 ring-primary/25">{{ auth()->user()->initials() }}</span>
                    @endif
                    <span class="hidden max-w-[8rem] truncate text-sm font-medium text-foreground sm:inline">{{ auth()->user()->name }}</span>
                </span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" aria-label="{{ __('frontend.navbar.sign-out') }}"
                            class="inline-flex items-center gap-1.5 rounded-xl px-3 py-2 text-sm font-medium text-foreground-muted transition hover:text-foreground">
                        <span class="material-symbols-outlined text-[1.2rem]">logout</span>
                        <span class="hidden sm:inline">{{ __('frontend.navbar.sign-out') }}</span>
                    </button>
                </form>
            @else
                <a href="{{ route('login') }}"
                   class="hidden rounded-xl px-3.5 py-2 text-sm font-medium text-foreground-muted transition hover:text-foreground sm:inline-flex">
                    {{ __('frontend.navbar.sign-in') }}
                </a>

                <a href="{{ route('register') }}"
                   class="inline-flex items-center gap-1.5 rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                    {{ __('frontend.navbar.start') }}
                    <span class="material-symbols-outlined text-[1.05rem]">south_east</span>
                </a>
            @endauth
        </div>
    </nav>
</header>
