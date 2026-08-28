{{-- The one thing to do after reading about a method.

     Same band on both method pages, because the answer to "interesting, now
     what" is the same in both places and a second variant of it would only be
     a second thing to maintain. --}}
<section {{ $attributes->class('rounded-2xl border border-primary/25 bg-primary/8 p-6 text-center sm:p-8') }}>
    <h2 class="text-balance font-display text-lg font-semibold text-foreground sm:text-xl">
        {{ __('frontend.methods.cta-title') }}
    </h2>
    <p class="mx-auto mt-2 max-w-md text-pretty text-sm leading-relaxed text-foreground-muted">
        {{ __('frontend.methods.cta-body') }}
    </p>

    <a href="{{ route('home') }}"
       class="group/cta mt-5 inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground transition duration-(--motion-feedback) ease-(--ease-snap) hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
        {{ __('frontend.methods.cta') }}
        <span class="dth-cta-arrow material-symbols-outlined text-[1.15rem]" aria-hidden="true">south_east</span>
    </a>
</section>
