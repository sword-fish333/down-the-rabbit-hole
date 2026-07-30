@props([
    'title',
    'subtitle' => null,
    'back' => null,
])

<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div class="min-w-0">
        @if ($back)
            <a href="{{ $back }}"
               class="mb-2 inline-flex items-center gap-1.5 text-xs font-medium text-muted-foreground transition hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40">
                <x-admin.icon name="arrow_back" class="text-base" />
                {{ __('admin/frontend.general.back') }}
            </a>
        @endif

        <h1 class="truncate font-display text-xl font-bold tracking-tight text-foreground lg:text-2xl">{{ $title }}</h1>

        @if ($subtitle)
            <p class="mt-1 text-sm text-muted-foreground">{{ $subtitle }}</p>
        @endif
    </div>

    @if (isset($actions))
        <div class="flex shrink-0 flex-wrap items-center gap-2">{{ $actions }}</div>
    @endif
</div>
