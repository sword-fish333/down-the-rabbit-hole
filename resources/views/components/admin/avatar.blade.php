@props([
    'admin',
    'size' => 'h-10 w-10',
    'text' => 'text-sm',
])

@php($url = $admin->profileImageUrl())

@if ($url)
    <img src="{{ $url }}" alt="{{ $admin->name }}"
         {{ $attributes->class([$size, 'rounded-full object-cover ring-2 ring-white dark:ring-zinc-800']) }}>
@else
    <span {{ $attributes->class([$size, $text, 'inline-flex items-center justify-center rounded-full bg-brand-600 font-semibold text-white ring-2 ring-white dark:ring-zinc-800']) }}>
        {{ $admin->initials() }}
    </span>
@endif
