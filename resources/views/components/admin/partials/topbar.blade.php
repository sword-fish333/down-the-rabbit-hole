@props(['title' => ''])

@php($admin = auth('admin')->user())

<header class="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-zinc-200 bg-white/80 px-4 backdrop-blur-md lg:px-6 dark:border-zinc-800 dark:bg-zinc-900/80">
    <button data-sidebar-open
            class="grid h-10 w-10 place-items-center rounded-lg text-zinc-600 hover:bg-zinc-100 lg:hidden dark:text-zinc-300 dark:hover:bg-zinc-800"
            aria-label="{{ __('admin/frontend.nav.open-menu') }}">
        <i class="fa-solid fa-bars text-lg"></i>
    </button>

    <h1 class="flex-1 truncate text-base font-semibold text-zinc-900 lg:text-lg dark:text-white">{{ $title }}</h1>

    {{-- Dark mode --}}
    <button data-theme-toggle
            class="grid h-10 w-10 place-items-center rounded-lg text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800"
            aria-label="{{ __('admin/frontend.nav.toggle-theme') }}">
        <i class="fa-solid fa-moon dark:hidden"></i>
        <i class="fa-solid fa-sun hidden dark:inline"></i>
    </button>

    {{-- Account menu --}}
    <div class="relative">
        <button data-dropdown-toggle="#user-menu"
                class="flex items-center gap-2 rounded-full p-1 pr-2 text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">
            <x-admin.avatar :admin="$admin" size="h-9 w-9" />
            <span class="hidden max-w-[10rem] truncate font-medium text-zinc-700 sm:block dark:text-zinc-200">{{ $admin->name }}</span>
            <i class="fa-solid fa-chevron-down hidden text-xs text-zinc-400 sm:block"></i>
        </button>

        <div id="user-menu" data-dropdown
             class="absolute right-0 mt-2 hidden w-60 overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-xl dark:border-zinc-800 dark:bg-zinc-900">
            <div class="border-b border-zinc-100 px-4 py-3 dark:border-zinc-800">
                <p class="truncate text-sm font-semibold text-zinc-900 dark:text-white">{{ $admin->name }}</p>
                <p class="truncate text-xs text-zinc-400">{{ $admin->email }}</p>
            </div>
            <div class="p-1.5">
                <a href="{{ route('admin.profile.index') }}"
                   class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800">
                    <i class="fa-solid fa-user-gear w-4 text-center text-zinc-400"></i>
                    {{ __('admin/frontend.nav.profile') }}
                </a>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit"
                            class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-500/10">
                        <i class="fa-solid fa-arrow-right-from-bracket w-4 text-center"></i>
                        {{ __('admin/frontend.nav.logout') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
