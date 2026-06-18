<x-admin.layout :title="__('admin/frontend.nav.dashboard')">
    {{-- Greeting --}}
    <div class="mb-6">
        <h2 class="text-xl font-bold tracking-tight text-zinc-900 dark:text-white">
            {{ __('admin/frontend.dashboard.greeting', ['name' => $admin->first_name ?: $admin->name]) }}
        </h2>
        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('admin/frontend.dashboard.subtitle') }}</p>
    </div>

    {{-- Stat cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @php($cards = [
            ['key' => 'users', 'icon' => 'fa-users', 'tone' => 'text-brand-600 bg-brand-50 dark:bg-brand-600/15'],
            ['key' => 'admins', 'icon' => 'fa-user-shield', 'tone' => 'text-emerald-600 bg-emerald-50 dark:bg-emerald-500/15'],
            ['key' => 'active_admins', 'icon' => 'fa-circle-check', 'tone' => 'text-amber-600 bg-amber-50 dark:bg-amber-500/15'],
        ])

        @foreach ($cards as $card)
            <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex items-center justify-between">
                    <span class="grid h-11 w-11 place-items-center rounded-xl {{ $card['tone'] }}">
                        <i class="fa-solid {{ $card['icon'] }}"></i>
                    </span>
                </div>
                <p class="mt-4 text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">
                    {{ number_format($stats[$card['key']]) }}
                </p>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('admin/frontend.dashboard.stats.'.$card['key']) }}
                </p>
            </div>
        @endforeach
    </div>

    {{-- Quick actions --}}
    <div class="mt-6 rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('admin/frontend.dashboard.quick-actions') }}</h3>
        <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
            <a href="{{ route('admin.profile.index') }}"
               class="flex items-center gap-3 rounded-xl border border-zinc-200 p-4 transition hover:border-brand-300 hover:bg-brand-50/50 dark:border-zinc-800 dark:hover:border-brand-600/40 dark:hover:bg-brand-600/10">
                <span class="grid h-10 w-10 place-items-center rounded-lg bg-brand-50 text-brand-600 dark:bg-brand-600/15">
                    <i class="fa-solid fa-user-gear"></i>
                </span>
                <span>
                    <span class="block text-sm font-semibold text-zinc-900 dark:text-white">{{ __('admin/frontend.nav.profile') }}</span>
                    <span class="block text-xs text-zinc-500 dark:text-zinc-400">{{ __('admin/frontend.dashboard.manage-profile') }}</span>
                </span>
            </a>
        </div>
    </div>
</x-admin.layout>
