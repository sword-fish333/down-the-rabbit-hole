@props([
    'tone' => 'muted',   // muted | primary | success | warning | danger
    'icon' => null,
])

@php
    $tones = [
        'muted' => 'border-border text-muted-foreground',
        'primary' => 'border-primary/35 bg-primary/8 text-primary',
        'success' => 'border-success/35 bg-success/8 text-success',
        'warning' => 'border-warning/40 bg-warning/10 text-warning-foreground dark:text-warning',
        'danger' => 'border-danger/35 bg-danger/8 text-danger',
    ];
@endphp

{{-- Always icon + word: status must never depend on colour alone. --}}
<span {{ $attributes->class(['inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-xs font-medium whitespace-nowrap', $tones[$tone] ?? $tones['muted']]) }}>
    @if ($icon)
        <x-admin.icon :name="$icon" class="text-[0.9rem]" />
    @endif
    {{ $slot }}
</span>
