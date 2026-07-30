@props([
    'title' => null,
    'subtitle' => null,
])

<section {{ $attributes->class('rounded-2xl border border-border bg-surface shadow-sm') }}>
    @if ($title)
        <header class="border-b border-border px-5 py-4">
            <h2 class="font-display text-base font-semibold text-foreground">{{ $title }}</h2>
            @if ($subtitle)
                <p class="mt-1 text-sm text-muted-foreground">{{ $subtitle }}</p>
            @endif
        </header>
    @endif

    <div class="p-5">{{ $slot }}</div>
</section>
