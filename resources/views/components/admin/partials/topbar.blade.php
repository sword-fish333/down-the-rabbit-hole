@props(['title' => ''])

@php($admin = auth('admin')->user())

<header class="sticky top-0 z-30 flex h-16 items-center gap-2 border-b border-border bg-surface/80 px-4 backdrop-blur-md lg:px-6">
    {{-- Mobile: open the off-canvas drawer --}}
    <button data-sidebar-open
            class="grid h-10 w-10 place-items-center rounded-lg text-muted-foreground hover:bg-muted hover:text-foreground lg:hidden"
            aria-label="{{ __('admin/frontend.nav.open-menu') }}">
        <x-admin.icon name="menu" />
    </button>

    {{-- Desktop: collapse the sidebar to an icon rail --}}
    <button data-sidebar-collapse
            class="hidden h-10 w-10 place-items-center rounded-lg text-muted-foreground hover:bg-muted hover:text-foreground lg:grid"
            aria-label="{{ __('admin/frontend.nav.toggle-sidebar') }}"
            title="{{ __('admin/frontend.nav.toggle-sidebar') }}">
        <x-admin.icon name="menu_open" class="transition-transform duration-300 nav-collapsed:rotate-180" />
    </button>

    <h1 class="flex-1 truncate text-base font-semibold text-foreground lg:text-lg">{{ $title }}</h1>

    {{-- Dark mode --}}
    <x-admin.theme-switch class="shrink-0" />

    {{-- Account menu --}}
    <div class="relative">
        <button data-dropdown-toggle="#user-menu"
                aria-haspopup="true" aria-expanded="false" aria-controls="user-menu"
                class="flex items-center gap-2 rounded-full p-1 pr-2 text-sm hover:bg-muted">
            <x-admin.avatar :admin="$admin" size="h-9 w-9" />
            <span class="hidden max-w-40 truncate font-medium text-foreground sm:block">{{ $admin->name }}</span>
            <x-admin.icon name="expand_more" class="hidden text-base text-muted-foreground sm:block" />
        </button>

        <div id="user-menu" data-dropdown
             class="absolute right-0 mt-2 hidden w-60 overflow-hidden rounded-2xl border border-border bg-surface shadow-xl">
            <div class="border-b border-border px-4 py-3">
                <p class="truncate text-sm font-semibold text-foreground">{{ $admin->name }}</p>
                <p class="truncate text-xs text-muted-foreground">{{ $admin->email }}</p>
            </div>
            <div class="p-1.5">
                <a href="{{ route('admin.profile.index') }}"
                   class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm text-muted-foreground hover:bg-muted hover:text-foreground">
                    <x-admin.icon name="manage_accounts" class="text-muted-foreground" />
                    {{ __('admin/frontend.nav.profile') }}
                </a>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit"
                            class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm text-danger hover:bg-danger/10">
                        <x-admin.icon name="logout" />
                        {{ __('admin/frontend.nav.logout') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
