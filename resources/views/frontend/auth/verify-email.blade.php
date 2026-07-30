<x-frontend.layout :title="__('frontend.auth.verify-title')">
    <section class="mx-auto flex min-h-[calc(100svh-4rem)] max-w-lg flex-col items-center justify-center px-4 py-16 text-center sm:px-6">
        <span class="grid h-14 w-14 place-items-center rounded-2xl bg-accent/12 text-accent ring-1 ring-accent/25">
            <span class="material-symbols-outlined text-[1.6rem]" aria-hidden="true">mark_email_unread</span>
        </span>

        <h1 class="mt-6 text-balance font-display text-2xl font-semibold tracking-tight text-foreground sm:text-3xl">
            {{ __('frontend.auth.verify-title') }}
        </h1>
        <p class="mt-3 text-pretty text-sm leading-relaxed text-foreground-muted">
            {{ __('frontend.auth.verify-body', ['email' => auth()->user()->email]) }}
        </p>

        <form method="POST" action="{{ route('verification.resend') }}" class="mt-8">
            @csrf
            <button type="submit"
                    class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground transition duration-(--motion-feedback) hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                <span class="material-symbols-outlined text-[1.1rem]" aria-hidden="true">send</span>
                {{ __('frontend.auth.verify-resend') }}
            </button>
        </form>

        {{-- Verification is not a gate: the way on is always available. --}}
        <a href="{{ route('holes.index') }}" class="mt-5 text-sm font-medium text-primary transition hover:text-primary/80">
            {{ __('frontend.auth.verify-skip') }}
        </a>
    </section>
</x-frontend.layout>
