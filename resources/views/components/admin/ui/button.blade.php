@props([
    'type' => 'button',
    'variant' => 'primary',
])

@php
    $variants = [
        'primary' => 'bg-brand-600 text-white shadow-sm hover:bg-brand-700 focus:ring-brand-500/40',
        'secondary' => 'bg-white text-zinc-700 ring-1 ring-zinc-200 hover:bg-zinc-50 dark:bg-zinc-800 dark:text-zinc-200 dark:ring-zinc-700 dark:hover:bg-zinc-700',
        'danger' => 'bg-rose-600 text-white shadow-sm hover:bg-rose-700 focus:ring-rose-500/40',
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
