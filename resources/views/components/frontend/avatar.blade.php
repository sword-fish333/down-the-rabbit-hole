@props([
    'user',
    'size' => 'h-9 w-9',
    'text' => 'text-xs',
])

@php($url = $user->profileImageUrl())

@if ($url)
    <img src="{{ $url }}" alt="" referrerpolicy="no-referrer" loading="lazy" decoding="async"
         {{ $attributes->class([$size, 'shrink-0 rounded-full object-cover ring-1 ring-border/60']) }}>
@else
    <span aria-hidden="true"
          {{ $attributes->class([$size, $text, 'grid shrink-0 place-items-center rounded-full bg-primary/12 font-semibold text-primary ring-1 ring-primary/25']) }}>
        {{ $user->initials() }}
    </span>
@endif
