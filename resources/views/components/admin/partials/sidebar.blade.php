@php($admin = auth('admin')->user())

<aside data-sidebar
       class="fixed inset-y-0 left-0 z-50 flex w-72 -translate-x-full flex-col border-r border-zinc-200 bg-white transition-transform duration-300 lg:static lg:z-auto lg:translate-x-0 dark:border-zinc-800 dark:bg-zinc-900">

    {{-- Brand --}}
    <div class="flex h-16 shrink-0 items-center justify-between gap-2 border-b border-zinc-200 px-5 dark:border-zinc-800">
        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5">
            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-600 text-white shadow-sm">
                <i class="fa-solid fa-comments"></i>
            </span>
            <span class="text-base font-bold tracking-tight text-zinc-900 dark:text-white">{{ config('app.name') }}</span>
        </a>
        <button data-sidebar-close
                class="grid h-9 w-9 place-items-center rounded-lg text-zinc-500 hover:bg-zinc-100 lg:hidden dark:hover:bg-zinc-800"
                aria-label="{{ __('admin/frontend.nav.close-menu') }}">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    {{-- Navigation --}}
    <nav class="flex-1 space-y-1 overflow-y-auto p-4">
        <p class="px-3.5 pb-2 text-xs font-semibold tracking-wider text-zinc-400 uppercase">
            {{ __('admin/frontend.nav.menu') }}
        </p>
        <x-admin.nav-link :href="route('admin.dashboard')" icon="fa-gauge-high"
                          :active="request()->routeIs('admin.dashboard')">
            {{ __('admin/frontend.nav.dashboard') }}
        </x-admin.nav-link>
        <x-admin.nav-link :href="route('admin.profile.index')" icon="fa-user-gear"
                          :active="request()->routeIs('admin.profile.*')">
            {{ __('admin/frontend.nav.profile') }}
        </x-admin.nav-link>
    </nav>

    {{-- Account summary --}}
    <div class="shrink-0 border-t border-zinc-200 p-4 dark:border-zinc-800">
        <div class="flex items-center gap-3">
            <x-admin.avatar :admin="$admin" />
            <div class="min-w-0">
                <p class="truncate text-sm font-semibold text-zinc-900 dark:text-white">{{ $admin->name }}</p>
                <p class="truncate text-xs text-zinc-400">{{ __('admin/frontend.roles.'.$admin->role) }}</p>
            </div>
        </div>
    </div>
</aside>
