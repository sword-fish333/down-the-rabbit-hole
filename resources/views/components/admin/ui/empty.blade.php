@props([
    'icon' => 'inbox',
    'title',
    'body' => null,
])

<div class="rounded-2xl border border-dashed border-border bg-muted/30 px-6 py-14 text-center">
    <x-admin.icon :name="$icon" class="text-3xl text-muted-foreground" />
    <h3 class="mt-3 font-display text-base font-semibold text-foreground">{{ $title }}</h3>

    @if ($body)
        <p class="mx-auto mt-1.5 max-w-md text-sm text-muted-foreground">{{ $body }}</p>
    @endif

    @if (isset($action))
        <div class="mt-6 flex justify-center">{{ $action }}</div>
    @endif
</div>
