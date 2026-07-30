@auth
    @if (! auth()->user()->hasVerifiedEmail())
        {{-- A reminder, not a gate. Verification protects the account and the
             streak; it does not stand between a learner and a layer, so this
             stays dismissible-by-completion rather than blocking. --}}
        <div class="mx-auto mb-6 flex max-w-3xl flex-col gap-3 rounded-2xl border border-accent/30 bg-accent/8 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="flex items-start gap-2.5 text-sm text-foreground">
                <span class="material-symbols-outlined mt-px shrink-0 text-[1.15rem] text-accent" aria-hidden="true">mark_email_unread</span>
                <span>{{ __('frontend.auth.verify-banner', ['email' => auth()->user()->email]) }}</span>
            </p>
            <form method="POST" action="{{ route('verification.resend') }}" class="shrink-0">
                @csrf
                <button type="submit"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-accent/40 px-3.5 py-2 text-xs font-semibold text-accent transition duration-(--motion-feedback) hover:bg-accent/12 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                    <span class="material-symbols-outlined text-[1rem]" aria-hidden="true">send</span>
                    {{ __('frontend.auth.verify-resend') }}
                </button>
            </form>
        </div>
    @endif
@endauth
