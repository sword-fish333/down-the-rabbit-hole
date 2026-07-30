<x-admin.layout :title="__('admin/frontend.learning-modes.title')">
    <x-admin.ui.page-header :title="__('admin/frontend.learning-modes.title')"
                            :subtitle="__('admin/frontend.learning-modes.subtitle')">
        <x-slot:actions>
            <a href="{{ route('admin.learning-mode.create') }}"
               class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm transition hover:bg-primary/90 focus:ring-2 focus:ring-primary/40 focus:outline-none">
                <x-admin.icon name="add" class="text-lg" />
                {{ __('admin/frontend.learning-modes.new') }}
            </a>
        </x-slot:actions>
    </x-admin.ui.page-header>

    <x-admin.ui.filters :action="route('admin.learning-mode.index')"
                        :placeholder="__('admin/frontend.learning-modes.search')">
        <x-admin.ui.select name="status" class="sm:w-44" :value="request('status')"
                           :placeholder="__('admin/frontend.general.all-statuses')"
                           :options="['enabled' => __('admin/frontend.general.enabled'), 'disabled' => __('admin/frontend.general.disabled')]" />
    </x-admin.ui.filters>

    @if ($modes->isEmpty())
        <x-admin.ui.empty icon="tune" :title="__('admin/frontend.learning-modes.empty-title')"
                          :body="__('admin/frontend.learning-modes.empty-body')">
            <x-slot:action>
                <a href="{{ route('admin.learning-mode.create') }}"
                   class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground">
                    <x-admin.icon name="add" class="text-lg" />
                    {{ __('admin/frontend.learning-modes.new') }}
                </a>
            </x-slot:action>
        </x-admin.ui.empty>
    @else
        {{-- Mobile-first: each mode is a card. From `lg` the same markup becomes
             a table-like grid row — one DOM tree, not two. --}}
        <div class="overflow-hidden rounded-2xl border border-border bg-surface shadow-sm">
            <div class="hidden border-b border-border bg-muted/40 px-5 py-3 lg:grid lg:grid-cols-[minmax(0,2.2fr)_minmax(0,3fr)_7rem_8rem_9rem] lg:gap-4">
                @foreach (['mode', 'directive', 'holes', 'status', ''] as $heading)
                    <p class="text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                        {{ $heading ? __('admin/frontend.learning-modes.col-'.$heading) : '' }}
                    </p>
                @endforeach
            </div>

            <ul class="divide-y divide-border">
                @foreach ($modes as $mode)
                    <li class="px-5 py-4 lg:grid lg:grid-cols-[minmax(0,2.2fr)_minmax(0,3fr)_7rem_8rem_9rem] lg:items-center lg:gap-4">
                        {{-- Mode --}}
                        <div class="flex min-w-0 items-start gap-3">
                            <span @class([
                                'mt-0.5 grid h-9 w-9 shrink-0 place-items-center rounded-xl ring-1',
                                'bg-primary/10 text-primary ring-primary/20' => $mode->accent === 'wave',
                                'bg-accent/10 text-accent ring-accent/20' => $mode->accent === 'sand',
                                'bg-success/10 text-success ring-success/20' => $mode->accent === 'sea',
                                'bg-clay-500/10 text-clay-500 ring-clay-500/20' => $mode->accent === 'clay',
                            ])>
                                <x-admin.icon :name="$mode->icon" class="text-lg" />
                            </span>
                            <div class="min-w-0">
                                <p class="flex flex-wrap items-center gap-2 font-medium text-foreground">
                                    {{ $mode->name }}
                                    @if ($mode->is_default)
                                        <x-admin.ui.badge tone="primary" icon="star">{{ __('admin/frontend.learning-modes.default') }}</x-admin.ui.badge>
                                    @endif
                                </p>
                                <p class="truncate font-mono text-xs text-muted-foreground">{{ $mode->slug }}</p>
                                @if ($mode->tagline)
                                    <p class="mt-1 text-sm text-muted-foreground lg:hidden">{{ $mode->tagline }}</p>
                                @endif
                            </div>
                        </div>

                        {{-- Directive (the actual behaviour, so it's worth showing) --}}
                        <p class="mt-3 line-clamp-2 text-sm text-muted-foreground lg:mt-0">{{ $mode->prompt_directive }}</p>

                        {{-- Usage --}}
                        <p class="mt-3 text-sm text-foreground lg:mt-0">
                            <span class="lg:hidden">{{ __('admin/frontend.learning-modes.col-holes') }}: </span>
                            <span class="font-mono tabular-nums">{{ $mode->conversations_count }}</span>
                        </p>

                        {{-- Status --}}
                        <div class="mt-3 lg:mt-0">
                            @if ($mode->enabled)
                                <x-admin.ui.badge tone="success" icon="check_circle">{{ __('admin/frontend.general.enabled') }}</x-admin.ui.badge>
                            @else
                                <x-admin.ui.badge icon="pause_circle">{{ __('admin/frontend.general.disabled') }}</x-admin.ui.badge>
                            @endif
                        </div>

                        {{-- Actions --}}
                        <div class="mt-4 flex items-center gap-1 lg:mt-0 lg:justify-end">
                            <form method="POST" action="{{ route('admin.learning-mode.toggle', $mode) }}" class="inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit"
                                        class="grid h-9 w-9 place-items-center rounded-lg text-muted-foreground transition hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40"
                                        title="{{ $mode->enabled ? __('admin/frontend.general.disable') : __('admin/frontend.general.enable') }}"
                                        aria-label="{{ $mode->enabled ? __('admin/frontend.general.disable') : __('admin/frontend.general.enable') }}">
                                    <x-admin.icon :name="$mode->enabled ? 'toggle_on' : 'toggle_off'" class="text-lg" />
                                </button>
                            </form>

                            <a href="{{ route('admin.learning-mode.edit', $mode) }}"
                               class="grid h-9 w-9 place-items-center rounded-lg text-muted-foreground transition hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40"
                               title="{{ __('admin/frontend.general.edit') }}" aria-label="{{ __('admin/frontend.general.edit') }}">
                                <x-admin.icon name="edit" class="text-lg" />
                            </a>

                            <x-admin.ui.delete-form :action="route('admin.learning-mode.destroy', $mode)" icon-only
                                                    :confirm="__('admin/frontend.learning-modes.confirm-delete', ['name' => $mode->name])" />
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="mt-5">{{ $modes->links() }}</div>
    @endif
</x-admin.layout>
