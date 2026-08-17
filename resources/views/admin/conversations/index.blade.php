@php
    use App\Models\Conversation;
    use App\Models\LearningMode;
@endphp

<x-admin.layout :title="__('admin/frontend.conversations.title')">
    <x-admin.ui.page-header :title="__('admin/frontend.conversations.title')"
                            :subtitle="__('admin/frontend.conversations.subtitle')" />

    {{-- The metrics worth watching. Not messages sent, not session length:
         both can rise while learning quality falls. --}}
    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        @foreach ([
            ['label' => 'total', 'value' => $stats['total'], 'icon' => 'forum'],
            ['label' => 'first-layer', 'value' => $stats['first_layer_rate'].'%', 'icon' => 'flag'],
            ['label' => 'average-depth', 'value' => $stats['average_depth'], 'icon' => 'south_east'],
            ['label' => 'surfaced', 'value' => $stats['surfaced'], 'icon' => 'workspace_premium'],
            ['label' => 'question-first', 'value' => $stats['question_first_rate'].'%', 'icon' => 'psychology_alt'],
        ] as $stat)
            <div class="rounded-2xl border border-border bg-surface p-4 shadow-sm">
                <div class="flex items-center gap-2">
                    <x-admin.icon :name="$stat['icon']" class="text-lg text-primary" />
                    <p class="text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                        {{ __('admin/frontend.conversations.stats.'.$stat['label']) }}
                    </p>
                </div>
                <p class="mt-2 font-display text-2xl font-bold tabular-nums text-foreground">{{ $stat['value'] }}</p>
            </div>
        @endforeach
    </div>

    <x-admin.ui.filters :action="route('admin.conversation.index')"
                        :placeholder="__('admin/frontend.conversations.search')">
        <x-admin.ui.select name="status" class="sm:w-48" :value="request('status')"
                           :placeholder="__('admin/frontend.general.all-statuses')"
                           :options="collect(Conversation::STATUSES)->mapWithKeys(fn ($s) => [$s => __('admin/frontend.conversations.status.'.$s)])->all()" />

        <x-admin.ui.select name="mode" class="sm:w-48" :value="request('mode')"
                           :placeholder="__('admin/frontend.conversations.all-modes')"
                           :options="LearningMode::query()->ordered()->pluck('name', 'id')->all()" />

        <x-admin.ui.select name="approach" class="sm:w-48" :value="request('approach')"
                           :placeholder="__('admin/frontend.conversations.all-approaches')"
                           :options="collect(Conversation::APPROACHES)->mapWithKeys(fn ($a) => [$a => __('admin/frontend.conversations.approach.'.$a)])->all()" />
    </x-admin.ui.filters>

    @if ($conversations->isEmpty())
        <x-admin.ui.empty icon="forum" :title="__('admin/frontend.conversations.empty-title')"
                          :body="__('admin/frontend.conversations.empty-body')" />
    @else
        <div class="overflow-hidden rounded-2xl border border-border bg-surface shadow-sm">
            <div class="hidden border-b border-border bg-muted/40 px-5 py-3 lg:grid lg:grid-cols-[minmax(0,2.4fr)_minmax(0,1.4fr)_6rem_6rem_10rem_5rem] lg:gap-4">
                @foreach (['subject', 'learner', 'depth', 'concepts', 'status', ''] as $heading)
                    <p class="text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                        {{ $heading ? __('admin/frontend.conversations.col-'.$heading) : '' }}
                    </p>
                @endforeach
            </div>

            <ul class="divide-y divide-border">
                @foreach ($conversations as $conversation)
                    <li class="px-5 py-4 lg:grid lg:grid-cols-[minmax(0,2.4fr)_minmax(0,1.4fr)_6rem_6rem_10rem_5rem] lg:items-center lg:gap-4">
                        <div class="min-w-0">
                            <a href="{{ route('admin.conversation.edit', $conversation) }}"
                               class="block truncate font-medium text-foreground transition hover:text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40">
                                {{ $conversation->displayTitle() }}
                            </a>
                            <p class="mt-0.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground">
                                @if ($conversation->learningMode)
                                    <span class="inline-flex items-center gap-1.5">
                                        <x-admin.icon :name="$conversation->learningMode->icon" class="text-sm" />
                                        {{ $conversation->learningMode->name }}
                                    </span>
                                @endif

                                {{-- Grounded in a page, and/or publicly readable: the two
                                     things a moderator needs to see without opening it. --}}
                                @if ($source = $conversation->sources->first())
                                    <span class="inline-flex min-w-0 items-center gap-1.5">
                                        <x-admin.icon name="link" class="text-sm" />
                                        <span class="truncate">{{ $source->site }}</span>
                                    </span>
                                @endif

                                @if ($conversation->isShared())
                                    <span class="inline-flex items-center gap-1.5 text-warning">
                                        <x-admin.icon name="public" class="text-sm" />
                                        {{ __('admin/frontend.conversations.public') }}
                                    </span>
                                @endif

                                {{-- Only flagged when the learner chose to be asked
                                     first — the default needs no label. --}}
                                @if ($conversation->opensWithQuestion())
                                    <span class="inline-flex items-center gap-1.5">
                                        <x-admin.icon name="psychology_alt" class="text-sm" />
                                        {{ __('admin/frontend.conversations.approach.question') }}
                                    </span>
                                @endif
                            </p>
                        </div>

                        <p class="mt-2 truncate text-sm text-muted-foreground lg:mt-0">
                            {{ $conversation->user?->name ?? __('admin/frontend.conversations.guest') }}
                        </p>

                        <p class="mt-2 text-sm lg:mt-0">
                            <span class="lg:hidden">{{ __('admin/frontend.conversations.col-depth') }}: </span>
                            <span class="font-mono tabular-nums text-foreground">{{ $conversation->current_depth }}</span>
                        </p>

                        <p class="mt-1 text-sm lg:mt-0">
                            <span class="lg:hidden">{{ __('admin/frontend.conversations.col-concepts') }}: </span>
                            <span class="font-mono tabular-nums text-foreground">{{ $conversation->concepts_count }}</span>
                        </p>

                        <div class="mt-3 lg:mt-0">
                            <x-admin.ui.badge :tone="match ($conversation->status) {
                                Conversation::STATUS_SURFACED => 'success',
                                Conversation::STATUS_CHECKPOINT_PENDING => 'primary',
                                default => 'muted',
                            }" :icon="match ($conversation->status) {
                                Conversation::STATUS_SURFACED => 'workspace_premium',
                                Conversation::STATUS_CHECKPOINT_PENDING => 'quiz',
                                default => 'south_east',
                            }">
                                {{ __('admin/frontend.conversations.status.'.$conversation->status) }}
                            </x-admin.ui.badge>
                        </div>

                        <div class="mt-4 flex items-center gap-1 lg:mt-0 lg:justify-end">
                            <a href="{{ route('admin.conversation.edit', $conversation) }}"
                               class="grid h-9 w-9 place-items-center rounded-lg text-muted-foreground transition hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40"
                               title="{{ __('admin/frontend.general.view') }}" aria-label="{{ __('admin/frontend.general.view') }}">
                                <x-admin.icon name="visibility" class="text-lg" />
                            </a>

                            <x-admin.ui.delete-form :action="route('admin.conversation.destroy', $conversation)" icon-only
                                                    :confirm="__('admin/frontend.conversations.confirm-delete')" />
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="mt-5">{{ $conversations->links() }}</div>
    @endif
</x-admin.layout>
