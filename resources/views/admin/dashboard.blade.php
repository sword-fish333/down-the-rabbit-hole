<x-admin.layout :title="__('admin/frontend.nav.dashboard')">
    {{-- Greeting --}}
    <div class="mb-6">
        <h2 class="text-xl font-bold tracking-tight text-foreground">
            {{ __('admin/frontend.dashboard.greeting', ['name' => $admin->first_name ?: $admin->name]) }}
        </h2>
        <p class="mt-1 text-sm text-muted-foreground">{{ __('admin/frontend.dashboard.subtitle') }}</p>
    </div>

    {{-- Stat cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @php($cards = [
            ['key' => 'users',         'icon' => 'group',         'tone' => 'text-wave-600 bg-wave-500/12'],
            ['key' => 'admins',        'icon' => 'shield_person', 'tone' => 'text-sea-600 bg-sea-500/12'],
            ['key' => 'active_admins', 'icon' => 'verified',      'tone' => 'text-clay-600 bg-clay-500/12'],
        ])

        @foreach ($cards as $card)
            <div class="rounded-2xl border border-border bg-surface p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="grid h-11 w-11 place-items-center rounded-xl {{ $card['tone'] }}">
                        <x-admin.icon :name="$card['icon']" filled class="text-2xl" />
                    </span>
                </div>
                <p class="mt-4 text-3xl font-bold tracking-tight text-foreground">
                    {{ number_format($stats[$card['key']]) }}
                </p>
                <p class="mt-1 text-sm text-muted-foreground">
                    {{ __('admin/frontend.dashboard.stats.'.$card['key']) }}
                </p>
            </div>
        @endforeach
    </div>

    {{-- Quick actions --}}
    <div class="mt-6 rounded-2xl border border-border bg-surface p-6 shadow-sm">
        <h3 class="text-sm font-semibold text-foreground">{{ __('admin/frontend.dashboard.quick-actions') }}</h3>
        <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
            <a href="{{ route('admin.profile.index') }}"
               class="group flex items-center gap-3 rounded-xl border border-border p-4 transition hover:border-primary/40 hover:bg-primary/5">
                <span class="grid h-10 w-10 place-items-center rounded-lg bg-primary/10 text-primary">
                    <x-admin.icon name="manage_accounts" />
                </span>
                <span>
                    <span class="block text-sm font-semibold text-foreground">{{ __('admin/frontend.nav.profile') }}</span>
                    <span class="block text-xs text-muted-foreground">{{ __('admin/frontend.dashboard.manage-profile') }}</span>
                </span>
            </a>
        </div>
    </div>
</x-admin.layout>
