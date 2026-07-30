@props([
    'label',
    'value',
    'icon' => null,
    'tone' => 'primary',   // primary | success | accent | muted
    'hint' => null,
])

@php
    $tones = [
        'primary' => 'text-primary',
        'success' => 'text-success',
        'accent' => 'text-accent',
        'muted' => 'text-foreground-muted',
    ];
@endphp

<div {{ $attributes->class('rounded-2xl border border-border/70 bg-surface/40 p-4 backdrop-blur-sm') }}>
    <div class="flex items-center gap-2">
        @if ($icon)
            <span class="material-symbols-outlined text-[1.1rem] {{ $tones[$tone] }}" aria-hidden="true">{{ $icon }}</span>
        @endif
        <p class="dth-coord">{{ $label }}</p>
    </div>
    <p class="mt-2 font-display text-2xl font-semibold tabular-nums text-foreground">{{ $value }}</p>
    @if ($hint)
        <p class="mt-1 text-xs text-foreground-muted/80">{{ $hint }}</p>
    @endif
</div>
