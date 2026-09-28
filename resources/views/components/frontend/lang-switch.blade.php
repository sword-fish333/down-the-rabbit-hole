@php($current = app()->getLocale())
@php($locales = \App\Http\Middleware\SetLocale::locales())

{{-- Language switcher. A segmented control of real links: no dropdown to open,
     no JavaScript, one tap to switch, and `aria-current` carries the state that
     colour alone would not.

     No flags — a flag names a country, not a language, and there is no correct
     flag for English. The code plus the endonym ("Română", never "Romanian") is
     what a visitor scans for, and each link carries `lang` so a screen reader
     says the endonym in its own language (WCAG 3.1.2).

     The slot is an optional visible label, drawn before the control where there
     is room for one — the subject rail, on a phone. It lives inside the
     component so a single-language install draws neither the switcher nor a
     label left pointing at nothing.

     ponytail: fine up to three languages. Past that this wants the collapsed
     <details> panel the mode picker uses, keyed off count($locales). --}}
@if (count($locales) > 1)
    <nav {{ $attributes->class(['flex shrink-0 items-center gap-3']) }}
         aria-label="{{ __('frontend.navbar.language') }}">
        {{ $slot }}

        {{-- Taller under a finger (pointer-coarse): about 25px clears the 24px
             minimum by a hair, about 33px is a target a thumb can find. --}}
        <div class="flex items-center gap-0.5 rounded-full border border-border bg-surface/40 p-0.5">
            @foreach ($locales as $code => $meta)
                <a href="{{ route('locale.switch', $code) }}"
                   hreflang="{{ $code }}"
                   lang="{{ $code }}"
                   @if ($code === $current) aria-current="true" @endif
                   title="{{ $meta['native'] }}"
                   @class([
                       'rounded-full px-2.5 py-1 font-mono text-[0.7rem] font-semibold uppercase tracking-wider transition duration-(--motion-feedback) ease-(--ease-snap) focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring pointer-coarse:py-2',
                       'bg-primary/12 text-primary' => $code === $current,
                       'text-foreground-muted hover:text-foreground' => $code !== $current,
                   ])>
                    {{ $code }}
                    <span class="sr-only">{{ $meta['native'] }}</span>
                </a>
            @endforeach
        </div>
    </nav>
@endif
