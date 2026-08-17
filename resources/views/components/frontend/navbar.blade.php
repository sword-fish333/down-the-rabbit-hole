@props(['peripheral' => false, 'shell' => false])

@php($user = auth()->user())

{{-- Frontend top bar. Translucent noir glass.

     Two shapes, one component. Off the app shell it is the whole navigation:
     brand left, destinations centre, account right. Inside the shell the rail
     already owns *subject* navigation, so this thins out — but the boards are
     not a subject, and they are the one destination that belongs up here in
     both shapes rather than in the rail.

     On the learning workspace it carries .dth-peripheral, so deep-work mode can
     dim it without removing it: the way out of a focus mode must stay reachable,
     just quieter. --}}
<header @class([
    'dth-glass sticky top-0 z-30 border-b border-border/70 bg-background/72 backdrop-blur-xl',
    'dth-peripheral' => $peripheral,
])>
    <nav @class([
             'mx-auto flex h-16 items-center gap-3 px-4 sm:px-6',
             'max-w-7xl justify-between lg:px-8' => ! $shell,
             'w-full' => $shell,
         ])
         aria-label="{{ __('frontend.navbar.primary') }}">

        @if ($shell)
            {{-- Drawer trigger — the rail is off-canvas below `lg`. --}}
            <button type="button" data-sidebar-open aria-controls="dth-sidebar" aria-expanded="false"
                    class="grid h-10 w-10 shrink-0 place-items-center rounded-xl border border-border text-foreground-muted transition duration-(--motion-feedback) hover:border-primary/40 hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring lg:hidden">
                <span class="material-symbols-outlined text-[1.25rem]" aria-hidden="true">menu</span>
                <span class="sr-only">{{ __('frontend.sidebar.open') }}</span>
            </button>

            {{-- Page title slot: the workspace fills it, everything else leaves it
                 empty so the bar stays a bar and not a second header. --}}
            <div class="min-w-0 flex-1">{{ $slot }}</div>
        @else
            <a href="{{ route('home') }}"
               class="group flex shrink-0 items-center gap-2.5 rounded-xl font-display text-base font-semibold tracking-tight text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                <img src="{{ loadFiles('images/logos/main_logo.png') }}"
                     alt="" width="36" height="36" loading="eager" decoding="async"
                     class="dth-logo h-9 w-9 shrink-0 rounded-full object-contain ring-1 ring-border/60">
                <span class="hidden sm:inline">{{ config('app.name') }}</span>
                <span class="sr-only sm:hidden">{{ config('app.name') }}</span>
            </a>

            @auth
                <div class="hidden items-center gap-1 md:flex">
                    <a href="{{ route('subjects.index') }}" @class([
                        'inline-flex items-center gap-2 rounded-xl px-3.5 py-2 text-sm font-medium transition duration-(--motion-feedback) ease-(--ease-snap)',
                        'bg-primary/10 text-primary' => request()->routeIs('subjects.*', 'subject.*'),
                        'text-foreground-muted hover:text-foreground' => ! request()->routeIs('subjects.*', 'subject.*'),
                    ])>
                        <span class="material-symbols-outlined text-[1.15rem]" aria-hidden="true">stairs</span>
                        {{ __('frontend.navbar.subjects') }}
                    </a>
                </div>
            @endauth
        @endif

        <div class="flex shrink-0 items-center gap-2 sm:gap-3">
            @auth
                {{-- The boards. Icon-only where the bar is tight, but the label is
                     never dropped for assistive tech. --}}
                @php($onRankings = request()->routeIs('rankings.*', 'learners.*'))

                <a href="{{ route('rankings.index') }}"
                   @if ($onRankings) aria-current="page" @endif
                   @class([
                       'inline-flex items-center gap-2 rounded-xl px-2.5 py-2 text-sm font-medium transition duration-(--motion-feedback) ease-(--ease-snap) focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring sm:px-3',
                       'bg-primary/10 text-primary' => $onRankings,
                       'text-foreground-muted hover:text-foreground' => ! $onRankings,
                   ])>
                    <span class="material-symbols-outlined text-[1.15rem]" aria-hidden="true">leaderboard</span>
                    <span class="hidden lg:inline">{{ __('frontend.navbar.rankings') }}</span>
                    <span class="sr-only lg:hidden">{{ __('frontend.navbar.rankings') }}</span>
                </a>

                {{-- The descent: days in a row with a layer cleared. Shown only
                     when it is alive — no zero-state guilt, no loss anxiety. --}}
                @if ($user->streak && $user->streak->current_count > 0)
                    <span class="hidden items-center gap-1.5 rounded-full border border-accent/30 bg-accent/8 px-3 py-1 font-mono text-xs text-accent sm:inline-flex"
                          title="{{ __('frontend.navbar.streak-title') }}">
                        <span class="material-symbols-outlined text-[1rem]" aria-hidden="true">local_fire_department</span>
                        {{ trans_choice('frontend.navbar.descent-days', $user->streak->current_count, ['count' => $user->streak->current_count]) }}
                    </span>
                @endif
            @endauth

            <x-frontend.lang-switch class="hidden sm:flex" />

            <x-frontend.theme-switch class="shrink-0" />

            @auth
                <div class="relative">
                    <button type="button" data-menu-toggle aria-controls="dth-account-menu"
                            aria-expanded="false" aria-haspopup="true"
                            class="flex items-center gap-2 rounded-full p-1 pr-2 text-sm transition duration-(--motion-feedback) hover:bg-surface-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                        <x-frontend.avatar :user="$user" />
                        <span class="hidden max-w-28 truncate font-medium text-foreground lg:inline">{{ $user->fullName() }}</span>
                        <span class="material-symbols-outlined text-[1.05rem] text-foreground-muted" aria-hidden="true">expand_more</span>
                        <span class="sr-only">{{ __('frontend.navbar.account') }}</span>
                    </button>

                    <div id="dth-account-menu" hidden
                         class="dth-stagger absolute right-0 mt-2 w-60 overflow-hidden rounded-2xl border border-border-strong bg-surface shadow-2xl">
                        <div class="border-b border-border px-4 py-3">
                            <p class="truncate text-sm font-semibold text-foreground">{{ $user->fullName() }}</p>
                            <p class="truncate text-xs text-foreground-muted">{{ $user->email }}</p>
                        </div>
                        <div class="p-1.5">
                            @unless ($shell)
                                <a href="{{ route('subjects.index') }}"
                                   class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm text-foreground-muted transition hover:bg-surface-muted hover:text-foreground md:hidden">
                                    <span class="material-symbols-outlined text-[1.15rem]" aria-hidden="true">stairs</span>
                                    {{ __('frontend.navbar.subjects') }}
                                </a>
                            @endunless

                            {{-- Their own public record, reachable without going
                                 through a board to find themselves on it. --}}
                            <a href="{{ route('learners.show', $user) }}"
                               class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm text-foreground-muted transition hover:bg-surface-muted hover:text-foreground">
                                <span class="material-symbols-outlined text-[1.15rem]" aria-hidden="true">badge</span>
                                {{ __('frontend.navbar.public-record') }}
                            </a>
                            <a href="{{ route('profile.index') }}"
                               class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm text-foreground-muted transition hover:bg-surface-muted hover:text-foreground">
                                <span class="material-symbols-outlined text-[1.15rem]" aria-hidden="true">manage_accounts</span>
                                {{ __('frontend.navbar.profile') }}
                            </a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"
                                        class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm text-foreground-muted transition hover:bg-surface-muted hover:text-foreground">
                                    <span class="material-symbols-outlined text-[1.15rem]" aria-hidden="true">logout</span>
                                    {{ __('frontend.navbar.sign-out') }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @else
                <a href="{{ route('login') }}"
                   class="hidden rounded-xl px-3.5 py-2 text-sm font-medium text-foreground-muted transition duration-(--motion-feedback) hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring sm:inline-flex">
                    {{ __('frontend.navbar.sign-in') }}
                </a>

                <a href="{{ route('register') }}"
                   class="group/cta inline-flex items-center gap-1.5 rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground transition duration-(--motion-feedback) ease-(--ease-snap) hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                    {{ __('frontend.navbar.start') }}
                    <span class="dth-cta-arrow material-symbols-outlined text-[1.05rem]" aria-hidden="true">south_east</span>
                </a>
            @endauth
        </div>
    </nav>
</header>
