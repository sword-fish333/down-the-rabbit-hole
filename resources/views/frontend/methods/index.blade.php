<x-frontend.layout :title="__('frontend.methods.title')"
                   :description="__('frontend.methods.meta')"
                   shell marketing>
    {{-- ===================================================================
         The reference desk.

         It exists because the product makes a pedagogical claim on every
         screen — prove it to descend, question first, rate your confidence —
         and a claim like that should be checkable. So each entry ends in a
         reading list, and each card here says what the technique is before it
         says where we use it.

         Deliberately outside the learning session: nothing links here from
         inside an open checkpoint. Reading about method while you are meant to
         be answering is the most respectable way to procrastinate there is.
         =================================================================== --}}
    <section class="mx-auto w-full max-w-[72rem] px-4 py-10 sm:px-6 sm:py-14 lg:px-8">
        <header class="max-w-2xl">
            <p class="dth-coord">{{ __('frontend.footer.how') }}</p>
            <h1 class="mt-2 text-balance font-display text-3xl font-semibold tracking-tight text-foreground sm:text-4xl">
                {{ __('frontend.methods.title') }}
            </h1>
            <p class="mt-4 text-pretty text-base leading-relaxed text-foreground-muted">
                {{ __('frontend.methods.subtitle') }}
            </p>
        </header>

        {{-- One column on a phone, two from `sm`, three on a wide desktop. The
             card is the whole link — no "read more" to aim at with a thumb. --}}
        <ul class="mt-10 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($methods as $slug => $method)
                <li>
                    <a href="{{ route('methods.show', $slug) }}"
                       class="dth-card group flex h-full flex-col rounded-2xl border border-border/70 bg-surface/40 p-5 backdrop-blur-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                        <span class="material-symbols-outlined text-[1.4rem] text-primary" aria-hidden="true">{{ $method['icon'] }}</span>

                        <h2 class="mt-3 font-display text-base font-semibold text-foreground">
                            {{ __('frontend.methods.items.'.$slug.'.name') }}
                        </h2>
                        <p class="mt-1.5 flex-1 text-sm leading-relaxed text-foreground-muted">
                            {{ __('frontend.methods.items.'.$slug.'.summary') }}
                        </p>

                        <span class="mt-4 inline-flex items-center gap-1.5 text-xs font-medium text-primary">
                            {{ __('frontend.methods.read') }}
                            <span class="dth-card-arrow material-symbols-outlined text-[1rem]" aria-hidden="true">arrow_forward</span>
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>

        <x-frontend.method-cta class="mt-12" />
    </section>
</x-frontend.layout>
