{{-- The Survey turn, named after the step it is.

     It carries the only in-session link to the method behind it, because this
     is the one turn where "what am I looking at" is a fair question — the
     learner asked for a page and got a map instead. New tab: an open descent
     is not what curiosity should cost. --}}
<p {{ $attributes->class('dth-coord mb-3 flex flex-wrap items-center gap-x-2.5 gap-y-1') }}>
    <span class="inline-flex items-center gap-1.5">
        <span class="material-symbols-outlined text-[1rem] text-primary" aria-hidden="true">travel_explore</span>
        {{ __('frontend.chat.survey') }}
    </span>

    <a href="{{ route('methods.show', 'sq3r') }}" target="_blank" rel="noopener"
       class="normal-case tracking-normal text-foreground-muted/85 underline decoration-border underline-offset-4 transition duration-(--motion-feedback) hover:text-foreground hover:decoration-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
        {{ __('frontend.methods.link', ['name' => __('frontend.methods.items.sq3r.name')]) }}
    </a>
</p>
