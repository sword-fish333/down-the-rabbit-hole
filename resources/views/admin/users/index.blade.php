<x-admin.layout :title="__('admin/frontend.users.title')">
    <x-admin.ui.page-header :title="__('admin/frontend.users.title')"
                            :subtitle="__('admin/frontend.users.subtitle')" />

    <x-admin.ui.filters :action="route('admin.user.index')" :placeholder="__('admin/frontend.users.search')">
        <x-admin.ui.select name="status" class="sm:w-44" :value="request('status')"
                           :placeholder="__('admin/frontend.general.all-statuses')"
                           :options="['enabled' => __('admin/frontend.general.enabled'), 'disabled' => __('admin/frontend.general.disabled')]" />
    </x-admin.ui.filters>

    @if ($users->isEmpty())
        <x-admin.ui.empty icon="group" :title="__('admin/frontend.users.empty-title')"
                          :body="__('admin/frontend.users.empty-body')" />
    @else
        <div class="overflow-hidden rounded-2xl border border-border bg-surface shadow-sm">
            <div class="hidden border-b border-border bg-muted/40 px-5 py-3 lg:grid lg:grid-cols-[minmax(0,2.4fr)_7rem_7rem_8rem_7rem] lg:gap-4">
                @foreach (['learner', 'subjects', 'xp', 'status', ''] as $heading)
                    <p class="text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                        {{ $heading ? __('admin/frontend.users.col-'.$heading) : '' }}
                    </p>
                @endforeach
            </div>

            <ul class="divide-y divide-border">
                @foreach ($users as $user)
                    <li class="px-5 py-4 lg:grid lg:grid-cols-[minmax(0,2.4fr)_7rem_7rem_8rem_7rem] lg:items-center lg:gap-4">
                        <div class="flex min-w-0 items-center gap-3">
                            <x-frontend.avatar :user="$user" size="h-9 w-9" />
                            <div class="min-w-0">
                                <p class="truncate font-medium text-foreground">{{ $user->fullName() }}</p>
                                <p class="truncate text-xs text-muted-foreground">{{ $user->email }}</p>
                            </div>
                        </div>

                        <p class="mt-3 text-sm text-foreground lg:mt-0">
                            <span class="lg:hidden">{{ __('admin/frontend.users.col-subjects') }}: </span>
                            <span class="font-mono tabular-nums">{{ $user->conversations_count }}</span>
                        </p>

                        <p class="mt-1 text-sm text-foreground lg:mt-0">
                            <span class="lg:hidden">{{ __('admin/frontend.users.col-xp') }}: </span>
                            <span class="font-mono tabular-nums">{{ number_format($user->xp) }}</span>
                        </p>

                        <div class="mt-3 flex flex-wrap items-center gap-2 lg:mt-0">
                            @if ($user->enabled)
                                <x-admin.ui.badge tone="success" icon="check_circle">{{ __('admin/frontend.general.enabled') }}</x-admin.ui.badge>
                            @else
                                <x-admin.ui.badge tone="danger" icon="block">{{ __('admin/frontend.general.disabled') }}</x-admin.ui.badge>
                            @endif

                            @unless ($user->hasVerifiedEmail())
                                <x-admin.ui.badge tone="warning" icon="mark_email_unread">{{ __('admin/frontend.users.unverified') }}</x-admin.ui.badge>
                            @endunless

                            {{-- Only flagged when it is true: a public listing is
                                 the state worth spotting from a list of hundreds. --}}
                            @if ($user->ranked)
                                <x-admin.ui.badge icon="leaderboard">{{ __('admin/frontend.users.col-ranked') }}</x-admin.ui.badge>
                            @endif
                        </div>

                        <div class="mt-4 flex items-center gap-1 lg:mt-0 lg:justify-end">
                            <a href="{{ route('admin.user.edit', $user) }}"
                               class="grid h-9 w-9 place-items-center rounded-lg text-muted-foreground transition hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40"
                               title="{{ __('admin/frontend.general.edit') }}" aria-label="{{ __('admin/frontend.general.edit') }}">
                                <x-admin.icon name="edit" class="text-lg" />
                            </a>

                            <x-admin.ui.delete-form :action="route('admin.user.destroy', $user)" icon-only
                                                    :confirm="__('admin/frontend.users.confirm-delete', ['name' => $user->fullName()])" />
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="mt-5">{{ $users->links() }}</div>
    @endif
</x-admin.layout>
