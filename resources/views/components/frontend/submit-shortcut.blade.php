@props(['id'])

<p id="{{ $id }}" {{ $attributes->class('mt-1 font-mono text-[0.65rem] text-foreground-muted/60') }}>
    <span class="hidden sm:inline" aria-hidden="true">{{ __('frontend.general.submit-shortcut-visual') }}</span>
    <span class="sr-only">{{ __('frontend.general.submit-shortcut') }}</span>
</p>
