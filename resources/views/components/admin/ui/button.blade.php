@props([
    'type' => 'button',
    'variant' => 'primary',
])

@php
    $variants = [
        'primary' => 'bg-primary text-primary-foreground shadow-sm hover:bg-primary/90 focus:ring-primary/40',
        'secondary' => 'bg-surface text-foreground ring-1 ring-border hover:bg-muted focus:ring-primary/30',
        'danger' => 'bg-danger text-danger-foreground shadow-sm hover:bg-danger/90 focus:ring-danger/40',
    ];
@endphp

<button
    type="{{ $type }}"
    {{ $attributes->class([
        'inline-flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold transition focus:ring-2 focus:outline-none disabled:cursor-not-allowed disabled:opacity-60',
        $variants[$variant] ?? $variants['primary'],
    ]) }}>
    {{ $slot }}
</button>
