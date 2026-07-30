<x-admin.layout :title="__('admin/frontend.nav.dashboard')">
    {{-- Greeting --}}
    <div class="mb-6">
        <h2 class="font-display text-xl font-bold tracking-tight text-foreground">
            {{ __('admin/frontend.dashboard.greeting', ['name' => $admin->first_name ?: $admin->name]) }}
        </h2>
        <p class="mt-1 text-sm text-muted-foreground">{{ __('admin/frontend.dashboard.subtitle') }}</p>
    </div>

    {{-- Platform scale --}}
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        @php($cards = [
            ['key' => 'users',         'icon' => 'group',         'tone' => 'text-wave-600 bg-wave-500/12'],
            ['key' => 'holes',         'icon' => 'forum',         'tone' => 'text-sea-600 bg-sea-500/12'],
            ['key' => 'admins',        'icon' => 'shield_person', 'tone' => 'text-sand-700 bg-sand-500/12'],
            ['key' => 'active_admins', 'icon' => 'verified',      'tone' => 'text-clay-600 bg-clay-500/12'],
        ])

        @foreach ($cards as $card)
            <div class="rounded-2xl border border-border bg-surface p-5 shadow-sm">
                <span class="grid h-11 w-11 place-items-center rounded-xl {{ $card['tone'] }}">
                    <x-admin.icon :name="$card['icon']" filled class="text-2xl" />
                </span>
                <p class="mt-4 font-display text-3xl font-bold tracking-tight tabular-nums text-foreground">
                    {{ number_format($stats[$card['key']]) }}
                </p>
                <p class="mt-1 text-sm text-muted-foreground">
                    {{ __('admin/frontend.dashboard.stats.'.$card['key']) }}
                </p>
            </div>
        @endforeach
    </div>

    {{-- ---------------------------------------------------------------
         Learning health. These are the numbers the product is actually
         optimising for; "messages sent" and "time on site" are deliberately
         absent, because both can rise while learning quality falls.
         --------------------------------------------------------------- --}}
    <section class="mt-6 rounded-2xl border border-border bg-surface p-6 shadow-sm">
        <h3 class="font-display text-sm font-semibold text-foreground">{{ __('admin/frontend.dashboard.learning') }}</h3>
        <p class="mt-1 text-xs text-muted-foreground">{{ __('admin/frontend.dashboard.learning-hint') }}</p>

        <dl class="mt-5 grid grid-cols-2 gap-x-4 gap-y-5 sm:grid-cols-3 lg:grid-cols-4">
            @foreach ([
                ['key' => 'first_layer_rate', 'value' => $learning['first_layer_rate'].'%', 'icon' => 'flag'],
                ['key' => 'layers_cleared', 'value' => number_format($learning['layers_cleared']), 'icon' => 'stairs'],
                ['key' => 'pass_rate', 'value' => $learning['pass_rate'].'%', 'icon' => 'task_alt'],
                ['key' => 'average_depth', 'value' => $learning['average_depth'], 'icon' => 'south_east'],
                ['key' => 'surfaced', 'value' => number_format($learning['surfaced']), 'icon' => 'workspace_premium'],
                ['key' => 'mastered_concepts', 'value' => number_format($learning['mastered_concepts']), 'icon' => 'psychology'],
                ['key' => 'open_misconceptions', 'value' => number_format($learning['open_misconceptions']), 'icon' => 'priority_high'],
            ] as $metric)
                <div>
                    <dt class="flex items-center gap-1.5 text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                        <x-admin.icon :name="$metric['icon']" class="text-base text-primary" />
                        {{ __('admin/frontend.dashboard.metrics.'.$metric['key']) }}
                    </dt>
                    <dd class="mt-1.5 font-display text-2xl font-bold tabular-nums text-foreground">{{ $metric['value'] }}</dd>
                </div>
            @endforeach
        </dl>
    </section>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        {{-- Mode usage: which pedagogy learners actually pick. --}}
        <x-admin.ui.card :title="__('admin/frontend.dashboard.modes')"
                         :subtitle="__('admin/frontend.dashboard.modes-hint')">
            @if ($modes->isEmpty())
                <p class="text-sm text-muted-foreground">{{ __('admin/frontend.dashboard.modes-empty') }}</p>
            @else
                @php($peak = max(1, $modes->max('conversations_count')))
                <ul class="space-y-3.5">
                    @foreach ($modes as $mode)
                        <li>
                            <div class="flex items-center justify-between gap-3 text-sm">
                                <span class="flex min-w-0 items-center gap-2">
                                    <x-admin.icon :name="$mode->icon" class="shrink-0 text-base text-muted-foreground" />
                                    <span class="truncate text-foreground">{{ $mode->name }}</span>
                                    @unless ($mode->enabled)
                                        <x-admin.ui.badge icon="pause_circle">{{ __('admin/frontend.general.disabled') }}</x-admin.ui.badge>
                                    @endunless
                                </span>
                                <span class="shrink-0 font-mono tabular-nums text-muted-foreground">{{ $mode->conversations_count }}</span>
                            </div>
                            {{-- A bar, not a chart library: one number per row. --}}
                            <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-muted" role="presentation">
                                <div class="h-full rounded-full bg-primary"
                                     style="width: {{ round($mode->conversations_count / $peak * 100) }}%"></div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-admin.ui.card>

        {{-- Recent activity --}}
        <x-admin.ui.card :title="__('admin/frontend.dashboard.recent')">
            @if ($recent->isEmpty())
                <p class="text-sm text-muted-foreground">{{ __('admin/frontend.dashboard.recent-empty') }}</p>
            @else
                <ul class="space-y-1">
                    @foreach ($recent as $hole)
                        <li>
                            <a href="{{ route('admin.conversation.edit', $hole) }}"
                               class="flex items-center justify-between gap-3 rounded-xl px-2.5 py-2 text-sm transition hover:bg-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40">
                                <span class="min-w-0">
                                    <span class="block truncate text-foreground">{{ $hole->displayTitle() }}</span>
                                    <span class="block truncate text-xs text-muted-foreground">
                                        {{ $hole->user?->name ?? __('admin/frontend.conversations.guest') }} · {{ $hole->updated_at->diffForHumans() }}
                                    </span>
                                </span>
                                <span class="shrink-0 font-mono text-xs text-muted-foreground">
                                    {{ str_pad($hole->current_depth, 2, '0', STR_PAD_LEFT) }}
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-admin.ui.card>
    </div>

    {{-- Quick actions --}}
    <section class="mt-6 rounded-2xl border border-border bg-surface p-6 shadow-sm">
        <h3 class="font-display text-sm font-semibold text-foreground">{{ __('admin/frontend.dashboard.quick-actions') }}</h3>
        <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ([
                ['route' => 'admin.learning-mode.index', 'icon' => 'tune', 'label' => 'nav.learning-modes', 'hint' => 'dashboard.manage-modes'],
                ['route' => 'admin.user.index', 'icon' => 'group', 'label' => 'nav.users', 'hint' => 'dashboard.manage-users'],
                ['route' => 'admin.conversation.index', 'icon' => 'forum', 'label' => 'nav.conversations', 'hint' => 'dashboard.manage-holes'],
            ] as $action)
                <a href="{{ route($action['route']) }}"
                   class="group flex items-center gap-3 rounded-xl border border-border p-4 transition hover:border-primary/40 hover:bg-primary/5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-primary/10 text-primary">
                        <x-admin.icon :name="$action['icon']" />
                    </span>
                    <span class="min-w-0">
                        <span class="block text-sm font-semibold text-foreground">{{ __('admin/frontend.'.$action['label']) }}</span>
                        <span class="block text-xs text-muted-foreground">{{ __('admin/frontend.'.$action['hint']) }}</span>
                    </span>
                </a>
            @endforeach
        </div>
    </section>
</x-admin.layout>
