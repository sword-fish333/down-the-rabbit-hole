{{-- ===========================================================================
     The subject rail — present on every app screen.

     It exists for two jobs and nothing else: get back into an unfinished
     descent, and see what you have been curious about. So it carries one primary
     action, one list, and two destinations. No feed, no badges, no promos.

     Data comes from a view composer (AppServiceProvider::composeSidebar) — the
     `dth_subject_view` cookie decides whether the flat list or the folder tree
     is built at all, so the unused one costs no queries and never flashes.

     Off-canvas below `lg` with a scrim; static from `lg` up. Position is CSS
     only, so it is usable before app.js runs.
     =========================================================================== --}}
@php($activeId = request()->route('conversation')?->id)

<div data-sidebar-scrim hidden
     class="fixed inset-0 z-40 bg-[oklch(0.12_0.02_248)]/70 backdrop-blur-sm lg:hidden"></div>

<aside id="dth-sidebar" data-sidebar aria-label="{{ __('frontend.sidebar.label') }}"
       class="dth-sidebar fixed inset-y-0 left-0 z-50 flex w-[17.5rem] max-w-[86vw] -translate-x-full flex-col border-r border-border/60 bg-surface/85 backdrop-blur-xl transition-transform duration-(--motion-panel) ease-(--ease-out) lg:translate-x-0">

    {{-- Brand. Doubles as the way out to the landing page. --}}
    <div class="flex h-16 shrink-0 items-center gap-2 px-3">
        <a href="{{ route('home') }}"
           class="group flex min-w-0 items-center gap-2.5 rounded-xl px-1 py-1 font-display text-sm font-semibold tracking-tight text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
            <img src="{{ loadFiles('images/logos/main_logo.png') }}"
                 alt="" width="32" height="32" loading="eager" decoding="async"
                 class="dth-logo h-8 w-8 shrink-0 rounded-full object-contain ring-1 ring-border/60">
            <span class="truncate">{{ config('app.name') }}</span>
        </a>

        <button type="button" data-sidebar-close
                class="ml-auto grid h-9 w-9 shrink-0 place-items-center rounded-xl text-foreground-muted transition duration-(--motion-feedback) hover:bg-surface-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring lg:hidden">
            <span class="material-symbols-outlined text-[1.2rem]" aria-hidden="true">close</span>
            <span class="sr-only">{{ __('frontend.sidebar.close') }}</span>
        </button>
    </div>

    {{-- The one primary action. Down is always down. --}}
    <div class="px-3">
        <a href="{{ route('home') }}"
           class="group/cta flex items-center gap-2.5 rounded-xl border border-primary/30 bg-primary/10 px-3 py-2.5 text-sm font-semibold text-primary transition duration-(--motion-feedback) ease-(--ease-snap) hover:border-primary/50 hover:bg-primary/15 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
            <span class="material-symbols-outlined text-[1.15rem]" aria-hidden="true">edit_square</span>
            {{ __('frontend.sidebar.new') }}
            <span class="dth-cta-arrow material-symbols-outlined ml-auto text-[1.05rem]" aria-hidden="true">south_east</span>
        </a>
    </div>

    @auth
        {{-- Section header + the list/folders switch. Two buttons rather than one
             that toggles, so the current view is stated rather than implied. --}}
        <div class="mt-6 flex items-center gap-2 px-4">
            <h2 id="dth-sidebar-heading" class="dth-coord">{{ __('frontend.sidebar.subjects') }}</h2>

            <div class="ml-auto flex items-center rounded-lg border border-border/70 p-0.5"
                 role="group" aria-label="{{ __('frontend.sidebar.view-label') }}">
                @foreach (['list' => 'view_agenda', 'folders' => 'folder_open'] as $view => $icon)
                    <button type="button" data-subject-view="{{ $view }}"
                            aria-pressed="{{ ($view === 'folders') === $folderView ? 'true' : 'false' }}"
                            title="{{ __('frontend.sidebar.view.'.$view) }}"
                            class="dth-view-toggle grid h-6 w-6 place-items-center rounded-md text-foreground-muted transition duration-(--motion-feedback) focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                        <span class="material-symbols-outlined text-[0.95rem]" aria-hidden="true">{{ $icon }}</span>
                        <span class="sr-only">{{ __('frontend.sidebar.view.'.$view) }}</span>
                    </button>
                @endforeach
            </div>
        </div>

        <nav class="scrollbar-slim mt-2 min-h-0 flex-1 overflow-y-auto px-2 pb-2"
             aria-labelledby="dth-sidebar-heading">
            @if ($folderView)
                @if ($tree['folders']->isEmpty() && $tree['unfiled']->isEmpty())
                    <p class="px-2 py-3 text-xs leading-relaxed text-foreground-muted/80">
                        {{ __('frontend.sidebar.empty') }}
                    </p>
                @else
                    @include('components.frontend.partials.sidebar-tree', [
                        'folders' => $tree['folders'],
                        'subjects' => $tree['unfiled'],
                        'activeId' => $activeId,
                    ])
                @endif
            @elseif ($recents->isEmpty())
                <p class="px-2 py-3 text-xs leading-relaxed text-foreground-muted/80">
                    {{ __('frontend.sidebar.empty') }}
                </p>
            @else
                <ul class="space-y-0.5">
                    @foreach ($recents as $subject)
                        <li>
                            <x-frontend.subject-link :subject="$subject" :active="$subject->id === $activeId" />
                        </li>
                    @endforeach
                </ul>
            @endif
        </nav>
    @else
        <div class="min-h-0 flex-1"></div>
    @endauth

    {{-- Foot: the two destinations, then who you are — or why signing in helps. --}}
    <div class="shrink-0 border-t border-border/60 p-2">
        @auth
            @foreach ([
                ['route' => 'subjects.index', 'icon' => 'history', 'key' => 'all', 'match' => 'subjects.index'],
                ['route' => 'subjects.organization', 'icon' => 'folder_managed', 'key' => 'organize', 'match' => 'subjects.organization'],
            ] as $item)
                <a href="{{ route($item['route']) }}" @class([
                    'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition duration-(--motion-feedback) ease-(--ease-snap) focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring',
                    'bg-primary/10 font-medium text-primary' => request()->routeIs($item['match']),
                    'text-foreground-muted hover:bg-surface-muted hover:text-foreground' => ! request()->routeIs($item['match']),
                ])>
                    <span class="material-symbols-outlined text-[1.2rem]" aria-hidden="true">{{ $item['icon'] }}</span>
                    {{ __('frontend.sidebar.'.$item['key']) }}
                </a>
            @endforeach
        @else
            {{-- Guests get one honest reason to sign in: the descent remembers
                 nothing until it has somewhere to remember it. --}}
            <div class="rounded-2xl border border-border/70 bg-background/40 p-4">
                <p class="font-display text-sm font-semibold text-foreground">{{ __('frontend.sidebar.guest-title') }}</p>
                <p class="mt-1.5 text-xs leading-relaxed text-foreground-muted">{{ __('frontend.sidebar.guest-body') }}</p>
                <a href="{{ route('login') }}"
                   class="mt-3 flex items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition duration-(--motion-feedback) ease-(--ease-snap) hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                    {{ __('frontend.sidebar.guest-cta') }}
                </a>
            </div>
        @endauth
    </div>
</aside>
