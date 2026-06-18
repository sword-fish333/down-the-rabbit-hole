@php($admin = auth('admin')->user())

<aside data-sidebar
       class="fixed inset-y-0 left-0 z-50 flex w-72 -translate-x-full flex-col border-r border-border bg-surface transition-all duration-300 lg:static lg:z-auto lg:w-72 lg:translate-x-0 nav-collapsed:lg:w-20">

    {{-- Brand --}}
    <div class="flex h-16 shrink-0 items-center justify-between gap-2 border-b border-border px-5 nav-collapsed:lg:justify-center nav-collapsed:lg:px-0">
        <x-admin.brand :href="route('admin.dashboard')"
                       name-class="text-base font-bold tracking-tight text-foreground nav-collapsed:lg:hidden" />
        <button data-sidebar-close
                class="grid h-9 w-9 shrink-0 place-items-center rounded-lg text-muted-foreground hover:bg-muted hover:text-foreground lg:hidden"
                aria-label="{{ __('admin/frontend.nav.close-menu') }}">
            <x-admin.icon name="close" />
        </button>
    </div>

    {{-- Navigation --}}
    <nav class="scrollbar-slim flex-1 space-y-1 overflow-y-auto p-4 nav-collapsed:lg:px-3">
        <p class="px-3.5 pb-2 text-xs font-semibold tracking-wider text-muted-foreground uppercase nav-collapsed:lg:hidden">
            {{ __('admin/frontend.nav.menu') }}
        </p>
        <x-admin.nav-link :href="route('admin.dashboard')" icon="space_dashboard"
                          :label="__('admin/frontend.nav.dashboard')"
                          :active="request()->routeIs('admin.dashboard')">
            {{ __('admin/frontend.nav.dashboard') }}
        </x-admin.nav-link>
        <x-admin.nav-link :href="route('admin.profile.index')" icon="manage_accounts"
                          :label="__('admin/frontend.nav.profile')"
                          :active="request()->routeIs('admin.profile.*')">
            {{ __('admin/frontend.nav.profile') }}
        </x-admin.nav-link>
    </nav>

    {{-- Account summary --}}
    <div class="shrink-0 border-t border-border p-4 nav-collapsed:lg:flex nav-collapsed:lg:justify-center nav-collapsed:lg:px-0">
        <div class="flex items-center gap-3">
            <x-admin.avatar :admin="$admin" />
            <div class="min-w-0 nav-collapsed:lg:hidden">
                <p class="truncate text-sm font-semibold text-foreground">{{ $admin->name }}</p>
                <p class="truncate text-xs text-muted-foreground">{{ __('admin/frontend.roles.'.$admin->role) }}</p>
            </div>
        </div>
    </div>
</aside>
