@php
    use App\Models\Concept;
    use App\Models\Message;

    $maxDepth = (int) config('platform.chat.max_depth');
    $tokens = $messages->sum(fn (Message $m) => (int) $m->input_tokens + (int) $m->output_tokens);
    $cacheReads = $messages->sum(fn (Message $m) => (int) $m->cache_read_tokens);
@endphp

<x-admin.layout :title="$conversation->displayTitle()">
    <x-admin.ui.page-header :title="$conversation->displayTitle()"
                            :subtitle="__('admin/frontend.conversations.transcript-subtitle')"
                            :back="route('admin.conversation.index')">
        <x-slot:actions>
            <x-admin.ui.delete-form :action="route('admin.conversation.destroy', $conversation)"
                                    :confirm="__('admin/frontend.conversations.confirm-delete')" />
        </x-slot:actions>
    </x-admin.ui.page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Transcript. Read-only by design: it is the learner's record, and the
             grading metrics are computed from it. --}}
        <div class="lg:col-span-2">
            <x-admin.ui.card :title="__('admin/frontend.conversations.transcript')">
                <ol class="space-y-5">
                    @foreach ($messages as $message)
                        <li>
                            <div class="mb-1.5 flex flex-wrap items-center gap-2">
                                <x-admin.ui.badge :tone="$message->role === Message::ROLE_USER ? 'primary' : 'muted'"
                                                  :icon="$message->role === Message::ROLE_USER ? 'person' : 'auto_awesome'">
                                    {{ __('admin/frontend.conversations.role.'.$message->role) }}
                                </x-admin.ui.badge>

                                @if ($message->phase)
                                    <x-admin.ui.badge icon="label">{{ __('admin/frontend.conversations.phase.'.$message->phase) }}</x-admin.ui.badge>
                                @endif

                                <span class="font-mono text-xs text-muted-foreground">
                                    {{ __('admin/frontend.conversations.layer') }} {{ $message->depth }}
                                </span>

                                @if ($message->model)
                                    <span class="font-mono text-xs text-muted-foreground">{{ $message->model }}</span>
                                @endif
                            </div>

                            <div class="rounded-xl border border-border bg-muted/25 px-4 py-3 text-sm leading-relaxed whitespace-pre-wrap text-foreground">{{ $message->content }}</div>
                        </li>
                    @endforeach
                </ol>
            </x-admin.ui.card>
        </div>

        <div class="space-y-6">
            <x-admin.ui.card :title="__('admin/frontend.conversations.overview')">
                <dl class="space-y-3 text-sm">
                    @foreach ([
                        'learner' => $conversation->user?->name ?? __('admin/frontend.conversations.guest'),
                        'mode' => $conversation->learningMode?->name ?? '—',
                        'depth' => $conversation->current_depth.' / '.$maxDepth,
                        'status' => __('admin/frontend.conversations.status.'.$conversation->status),
                        'messages' => $conversation->message_count,
                        'tokens' => number_format($tokens),
                        'cache-reads' => number_format($cacheReads),
                    ] as $key => $value)
                        <div class="flex items-start justify-between gap-3">
                            <dt class="text-muted-foreground">{{ __('admin/frontend.conversations.field.'.$key) }}</dt>
                            <dd class="text-right font-medium text-foreground">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            </x-admin.ui.card>

            @if ($conversation->concepts->isNotEmpty())
                <x-admin.ui.card :title="__('admin/frontend.conversations.mastery')">
                    <ul class="space-y-2">
                        @foreach ($conversation->concepts as $concept)
                            <li class="flex items-center justify-between gap-3 text-sm">
                                <span class="min-w-0 truncate text-foreground">{{ $concept->name }}</span>
                                <x-admin.ui.badge :tone="match ($concept->state) {
                                    Concept::STATE_MASTERED => 'success',
                                    Concept::STATE_MISUNDERSTOOD => 'warning',
                                    Concept::STATE_DEVELOPING => 'primary',
                                    default => 'muted',
                                }" :icon="match ($concept->state) {
                                    Concept::STATE_MASTERED => 'verified',
                                    Concept::STATE_MISUNDERSTOOD => 'priority_high',
                                    Concept::STATE_DEVELOPING => 'trending_up',
                                    default => 'radio_button_unchecked',
                                }">
                                    {{ __('admin/frontend.conversations.concept-state.'.$concept->state) }}
                                </x-admin.ui.badge>
                            </li>
                        @endforeach
                    </ul>
                </x-admin.ui.card>
            @endif

            @if ($conversation->checkpointAttempts->isNotEmpty())
                <x-admin.ui.card :title="__('admin/frontend.conversations.attempts')"
                                 :subtitle="__('admin/frontend.conversations.attempts-hint')">
                    <ul class="space-y-2.5">
                        @foreach ($conversation->checkpointAttempts as $attempt)
                            <li class="flex items-center justify-between gap-3 text-sm">
                                <span class="font-mono text-xs text-muted-foreground">
                                    {{ __('admin/frontend.conversations.layer') }} {{ $attempt->depth }}
                                </span>
                                <span class="flex items-center gap-2">
                                    <span class="font-mono tabular-nums text-foreground">{{ $attempt->score }}</span>
                                    <x-admin.ui.badge :tone="$attempt->passed() ? 'success' : 'warning'"
                                                      :icon="$attempt->passed() ? 'check_circle' : 'replay'">
                                        {{ __('admin/frontend.conversations.verdict.'.$attempt->verdict) }}
                                    </x-admin.ui.badge>
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </x-admin.ui.card>
            @endif

            @if ($conversation->summary)
                <x-admin.ui.card :title="__('admin/frontend.conversations.summary')"
                                 :subtitle="__('admin/frontend.conversations.summary-hint')">
                    <p class="text-sm leading-relaxed text-muted-foreground">{{ $conversation->summary }}</p>
                </x-admin.ui.card>
            @endif
        </div>
    </div>
</x-admin.layout>
