@props([
    'admin',
    'size' => 'h-10 w-10',
    'text' => 'text-sm',
])

@php($url = $admin->profileImageUrl())

@if ($url)
    <img src="{{ $url }}" alt="{{ $admin->name }}"
         {{ $attributes->class([$size, 'rounded-full object-cover ring-2 ring-surface']) }}>
@else
    <span {{ $attributes->class([$size, $text, 'inline-flex items-center justify-center rounded-full bg-primary font-semibold text-primary-foreground ring-2 ring-surface']) }}>
        {{ $admin->initials() }}
    </span>
@endif
