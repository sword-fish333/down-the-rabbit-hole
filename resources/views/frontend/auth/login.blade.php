<x-frontend.layout :title="__('frontend.auth.login-title')">
    {{-- Mobile-first: the form is the whole screen; the brand showcase only
         appears on lg+ as the left half of a split. --}}
    <section class="relative flex min-h-[calc(100svh-4rem)] w-full">
        <x-frontend.auth-aside />

        <div class="flex w-full flex-col justify-center px-4 py-12 sm:px-6 lg:w-1/2 lg:px-16">
            <div class="mx-auto w-full max-w-md">
                <h1 class="font-display text-2xl font-semibold tracking-tight text-foreground sm:text-3xl">{{ __('frontend.auth.login-title') }}</h1>
                <p class="mt-2 text-sm text-foreground-muted">{{ __('frontend.auth.login-subtitle') }}</p>

                @if (session('error'))
                    <p role="alert" class="mt-5 flex items-center gap-2 rounded-xl border border-danger/40 bg-danger/10 px-4 py-2.5 text-sm text-danger">
                        <span class="material-symbols-outlined text-[1.1rem]" aria-hidden="true">error</span>{{ session('error') }}
                    </p>
                @endif

                <form action="{{ route('login.submit') }}" method="POST" class="mt-7 space-y-5">
                    @csrf

                    <x-frontend.text-field name="email" type="email" :label="__('frontend.auth.email')"
                                           icon="mail" required autofocus autocomplete="email"
                                           :placeholder="__('frontend.auth.email-placeholder')" />

                    <x-frontend.text-field name="password" type="password" :label="__('frontend.auth.password')"
                                           icon="lock" required autocomplete="current-password"
                                           :placeholder="__('frontend.auth.password-placeholder')">
                        <button type="button" data-password-toggle="#password" aria-pressed="false"
                                aria-label="{{ __('frontend.auth.toggle-password') }}"
                                class="absolute right-3.5 top-1/2 -translate-y-1/2 rounded-lg text-foreground-muted/70 transition hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                            <span class="material-symbols-outlined text-[1.2rem]" aria-hidden="true">visibility</span>
                        </button>
                    </x-frontend.text-field>

                    <label class="flex cursor-pointer select-none items-center gap-2.5 text-sm text-foreground-muted">
                        <input type="checkbox" name="remember" value="1"
                               class="h-4 w-4 rounded border-border-strong bg-surface text-primary focus:ring-2 focus:ring-ring/40">
                        {{ __('frontend.auth.remember') }}
                    </label>

                    <button type="submit"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-5 py-3 text-sm font-semibold text-primary-foreground transition duration-(--motion-feedback) ease-(--ease-snap) hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                        {{ __('frontend.auth.login-cta') }}
                        <span class="material-symbols-outlined text-[1.15rem]" aria-hidden="true">login</span>
                    </button>
                </form>

                <div class="my-7 flex items-center gap-4">
                    <span class="h-px flex-1 bg-border"></span>
                    <span class="dth-coord">{{ __('frontend.auth.or') }}</span>
                    <span class="h-px flex-1 bg-border"></span>
                </div>

                <x-frontend.google-button :label="__('frontend.auth.continue-with-google')" />

                <p class="mt-7 text-center text-sm text-foreground-muted">
                    {{ __('frontend.auth.no-account') }}
                    <a href="{{ route('register') }}" class="font-medium text-primary transition hover:text-primary/80">{{ __('frontend.auth.go-register') }}</a>
                </p>
            </div>
        </div>
    </section>
</x-frontend.layout>
