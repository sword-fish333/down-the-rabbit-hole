@props([
    'href' => null,                 // wraps in <a> when set, <div> otherwise
    'logoClass' => 'h-9 w-9',
    'nameClass' => 'text-base font-bold tracking-tight text-foreground',
    'showName' => true,
])
{{--
    Logo + wordmark lockup. Single source of truth for the brand mark, reused in
    the sidebar and the login screen. Pass a collapse-aware nameClass
    (e.g. "... nav-collapsed:lg:hidden") to hide the wordmark when the rail collapses.
--}}
@php($tag = $href ? 'a' : 'div')

<{{ $tag }} @if ($href) href="{{ $href }}" @endif {{ $attributes->class('flex items-center gap-2.5') }}>
    <img src="{{ loadFiles('images/logos/main_logo.png') }}"
         alt="{{ config('app.name') }}"
         width="36" height="36"
         class="{{ $logoClass }} shrink-0 rounded-xl object-contain">
    @if ($showName)
        <span class="{{ $nameClass }} font-display truncate">{{ config('app.name') }}</span>
    @endif
</{{ $tag }}>
