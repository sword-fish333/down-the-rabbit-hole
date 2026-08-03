@props(['subject', 'compact' => false])

{{-- Where a subject stands. Colour never carries this alone: every state has its
     own icon and its own word, and the compact form keeps the word for screen
     readers and as the hover title. --}}
@php
    $state = match (true) {
        $subject->isSurfaced() => 'surfaced',
        $subject->isCheckpointPending() => 'checkpoint',
        default => 'exploring',
    };

    $icon = ['surfaced' => 'workspace_premium', 'checkpoint' => 'quiz', 'exploring' => 'south_east'][$state];
    $text = ['surfaced' => 'text-success', 'checkpoint' => 'text-primary', 'exploring' => 'text-foreground-muted'][$state];
    $edge = [
        'surfaced' => 'border-success/35 bg-success/8',
        'checkpoint' => 'border-primary/35 bg-primary/8',
        'exploring' => 'border-border',
    ][$state];

    $label = __('frontend.subjects.status.'.$state);
@endphp

@if ($compact)
    <span class="material-symbols-outlined shrink-0 text-[0.95rem] {{ $text }}" title="{{ $label }}" aria-hidden="true">{{ $icon }}</span>
    <span class="sr-only">{{ $label }}</span>
@else
    <span {{ $attributes->class(['inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-xs', $text, $edge]) }}>
        <span class="material-symbols-outlined text-[0.9rem]" aria-hidden="true">{{ $icon }}</span>
        {{ $label }}
    </span>
@endif
