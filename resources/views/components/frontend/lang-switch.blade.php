@php($current = app()->getLocale())
@php($locales = \App\Http\Middleware\SetLocale::locales())

{{-- Language switcher. A segmented control of real links: no dropdown to open,
     no JavaScript, one tap to switch, and `aria-current` carries the state that
     colour alone would not.

     No flags — a flag names a country, not a language, and there is no correct
     flag for English. The code plus the endonym ("Română", never "Romanian") is
     what a visitor scans for.

     ponytail: fine up to three languages. Past that this wants the collapsed
     <details> panel the mode picker uses, keyed off count($locales). --}}
@if (count($locales) > 1)
    <nav {{ $attributes->class(['flex shrink-0 items-center gap-0.5 rounded-full border border-border bg-surface/40 p-0.5']) }}
         aria-label="{{ __('frontend.navbar.language') }}">
        @foreach ($locales as $code => $meta)
            <a href="{{ route('locale.switch', $code) }}"
               hreflang="{{ $code }}"
               @if ($code === $current) aria-current="true" @endif
               title="{{ $meta['native'] }}"
               @class([
                   'rounded-full px-2.5 py-1 font-mono text-[0.7rem] font-semibold uppercase tracking-wider transition duration-(--motion-feedback) ease-(--ease-snap) focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring',
                   'bg-primary/12 text-primary' => $code === $current,
                   'text-foreground-muted hover:text-foreground' => $code !== $current,
               ])>
                {{ $code }}
                <span class="sr-only">{{ $meta['native'] }}</span>
            </a>
        @endforeach
    </nav>
@endif
