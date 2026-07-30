<x-frontend.layout :title="__('frontend.auth.register-title')">
    {{-- Mobile-first: the form is the whole screen; the brand showcase only
         appears on lg+ as the left half of a split. --}}
    <section class="relative flex min-h-[calc(100svh-4rem)] w-full">
        <x-frontend.auth-aside />

        <div class="flex w-full flex-col justify-center px-4 py-12 sm:px-6 lg:w-1/2 lg:px-16">
            <div class="mx-auto w-full max-w-md">
                <h1 class="font-display text-2xl font-semibold tracking-tight text-foreground sm:text-3xl">{{ __('frontend.auth.register-title') }}</h1>
                <p class="mt-2 text-sm text-foreground-muted">{{ __('frontend.auth.register-subtitle') }}</p>

                @if (session('error'))
                    <p role="alert" class="mt-5 flex items-center gap-2 rounded-xl border border-danger/40 bg-danger/10 px-4 py-2.5 text-sm text-danger">
                        <span class="material-symbols-outlined text-[1.1rem]" aria-hidden="true">error</span>{{ session('error') }}
                    </p>
                @endif

                <form action="{{ route('register.submit') }}" method="POST" class="mt-7 space-y-5">
                    @csrf

                    {{-- Split name: `name` is derived from these on save, so the
                         rest of the app keeps reading a single display name. --}}
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-frontend.text-field name="first_name" :label="__('frontend.auth.first-name')"
                                               icon="person" required autofocus autocomplete="given-name"
                                               :placeholder="__('frontend.auth.first-name-placeholder')" />

                        <x-frontend.text-field name="last_name" :label="__('frontend.auth.last-name')"
                                               autocomplete="family-name"
                                               :placeholder="__('frontend.auth.last-name-placeholder')" />
                    </div>

                    <x-frontend.text-field name="email" type="email" :label="__('frontend.auth.email')"
                                           icon="mail" required autocomplete="email"
                                           :placeholder="__('frontend.auth.email-placeholder')"
                                           :hint="__('frontend.auth.email-hint')" />

                    <x-frontend.text-field name="password" type="password" :label="__('frontend.auth.password')"
                                           icon="lock" required autocomplete="new-password"
                                           :placeholder="__('frontend.auth.password-placeholder')"
                                           :hint="__('frontend.auth.password-hint')">
                        <button type="button" data-password-toggle="#password" aria-pressed="false"
                                aria-label="{{ __('frontend.auth.toggle-password') }}"
                                class="absolute right-3.5 top-1/2 -translate-y-1/2 rounded-lg text-foreground-muted/70 transition hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                            <span class="material-symbols-outlined text-[1.2rem]" aria-hidden="true">visibility</span>
                        </button>
                    </x-frontend.text-field>

                    <x-frontend.text-field name="password_confirmation" type="password" :label="__('frontend.auth.password-confirm')"
                                           icon="lock" required autocomplete="new-password"
                                           :placeholder="__('frontend.auth.password-placeholder')" />

                    <button type="submit"
                            class="group/cta inline-flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-5 py-3 text-sm font-semibold text-primary-foreground transition duration-(--motion-feedback) ease-(--ease-snap) hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                        {{ __('frontend.auth.register-cta') }}
                        <span class="dth-cta-arrow material-symbols-outlined text-[1.15rem]" aria-hidden="true">south_east</span>
                    </button>
                </form>

                <div class="my-7 flex items-center gap-4">
                    <span class="h-px flex-1 bg-border"></span>
                    <span class="dth-coord">{{ __('frontend.auth.or') }}</span>
                    <span class="h-px flex-1 bg-border"></span>
                </div>

                <x-frontend.google-button :label="__('frontend.auth.continue-with-google')" />

                <p class="mt-7 text-center text-sm text-foreground-muted">
                    {{ __('frontend.auth.have-account') }}
                    <a href="{{ route('login') }}" class="font-medium text-primary transition hover:text-primary/80">{{ __('frontend.auth.go-login') }}</a>
                </p>
            </div>
        </div>
    </section>
</x-frontend.layout>
