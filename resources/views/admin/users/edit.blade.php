<x-admin.layout :title="$user->fullName()">
    <x-admin.ui.page-header :title="$user->fullName()" :subtitle="$user->email"
                            :back="route('admin.user.index')">
        <x-slot:actions>
            <x-admin.ui.delete-form :action="route('admin.user.destroy', $user)"
                                    :confirm="__('admin/frontend.users.confirm-delete', ['name' => $user->fullName()])" />
        </x-slot:actions>
    </x-admin.ui.page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <form method="POST" action="{{ route('admin.user.update', $user) }}">
                @csrf
                @method('PUT')

                <x-admin.ui.card :title="__('admin/frontend.users.section-details')">
                    <div class="space-y-5">
                        <div class="grid gap-5 sm:grid-cols-2">
                            <x-admin.ui.input name="first_name" :label="__('admin/frontend.profile.first-name')" :value="$user->first_name" />
                            <x-admin.ui.input name="last_name" :label="__('admin/frontend.profile.last-name')" :value="$user->last_name" />
                        </div>

                        <x-admin.ui.input name="email" type="email" :label="__('admin/frontend.auth.email')" :value="$user->email" required />
                        <x-admin.ui.input name="phone" :label="__('admin/frontend.profile.phone')" :value="$user->phone" />

                        <x-admin.ui.toggle name="enabled" :label="__('admin/frontend.users.enabled')"
                                           :checked="$user->enabled" :hint="__('admin/frontend.users.enabled-hint')" />
                    </div>
                </x-admin.ui.card>

                <div class="mt-6">
                    <x-admin.ui.button type="submit">
                        <x-admin.icon name="save" class="text-lg" />
                        {{ __('admin/frontend.general.save') }}
                    </x-admin.ui.button>
                </div>
            </form>
        </div>

        <div class="space-y-6">
            {{-- Learning record, read-only. Depth and mastery, never time spent. --}}
            <x-admin.ui.card :title="__('admin/frontend.users.section-record')">
                <dl class="space-y-3 text-sm">
                    @foreach ([
                        'holes' => $user->conversations_count,
                        'xp' => number_format($user->xp),
                        'streak' => $streak?->current_count ?? 0,
                        'longest-streak' => $streak?->longest_count ?? 0,
                    ] as $key => $value)
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-muted-foreground">{{ __('admin/frontend.users.record.'.$key) }}</dt>
                            <dd class="font-mono tabular-nums text-foreground">{{ $value }}</dd>
                        </div>
                    @endforeach

                    <div class="flex items-center justify-between gap-3 border-t border-border pt-3">
                        <dt class="text-muted-foreground">{{ __('admin/frontend.users.record.verified') }}</dt>
                        <dd>
                            @if ($user->hasVerifiedEmail())
                                <x-admin.ui.badge tone="success" icon="verified">{{ $user->email_verified_at->format('j M Y') }}</x-admin.ui.badge>
                            @else
                                <x-admin.ui.badge tone="warning" icon="mark_email_unread">{{ __('admin/frontend.users.unverified') }}</x-admin.ui.badge>
                            @endif
                        </dd>
                    </div>
                </dl>
            </x-admin.ui.card>

            @if ($holes->isNotEmpty())
                <x-admin.ui.card :title="__('admin/frontend.users.section-holes')">
                    <ul class="space-y-2.5">
                        @foreach ($holes as $hole)
                            <li>
                                <a href="{{ route('admin.conversation.edit', $hole) }}"
                                   class="flex items-center justify-between gap-3 rounded-xl px-2.5 py-2 text-sm transition hover:bg-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40">
                                    <span class="min-w-0 truncate text-foreground">{{ $hole->displayTitle() }}</span>
                                    <span class="shrink-0 font-mono text-xs text-muted-foreground">
                                        {{ str_pad($hole->current_depth, 2, '0', STR_PAD_LEFT) }}
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </x-admin.ui.card>
            @endif
        </div>
    </div>
</x-admin.layout>
