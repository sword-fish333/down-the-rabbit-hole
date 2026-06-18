<x-admin.guest-layout :title="__('admin/frontend.auth.login-title')">
    <div class="flex min-h-screen">

        {{-- Brand panel (desktop only) --}}
        <div class="relative hidden w-1/2 flex-col justify-between overflow-hidden p-12 text-white lg:flex">
            <div class="absolute inset-0 bg-gradient-to-br from-wave-700 via-wave-800 to-wave-950"></div>
            <div class="absolute -top-24 -right-24 h-80 w-80 rounded-full bg-foam-400/20 blur-3xl"></div>
            <div class="absolute -bottom-24 -left-10 h-72 w-72 rounded-full bg-sand-400/20 blur-3xl"></div>

            <x-admin.brand class="relative"
                           logo-class="h-11 w-11"
                           name-class="text-lg font-bold tracking-tight text-white" />

            <div class="relative max-w-md">
                <h2 class="text-4xl leading-tight font-bold tracking-tight">
                    {{ __('admin/frontend.auth.brand-headline') }}
                </h2>
                <p class="mt-4 text-base text-white/70">{{ __('admin/frontend.auth.brand-subline') }}</p>

                <ul class="mt-10 space-y-4">
                    @foreach (['secure', 'realtime', 'control'] as $feature)
                        <li class="flex items-center gap-3 text-sm text-white/85">
                            <span class="grid h-8 w-8 place-items-center rounded-lg bg-white/10">
                                <x-admin.icon name="check" class="text-base" />
                            </span>
                            {{ __('admin/frontend.auth.feature-'.$feature) }}
                        </li>
                    @endforeach
                </ul>
            </div>

            <p class="relative text-xs text-white/50">
                &copy; {{ date('Y') }} {{ config('app.name') }}. {{ __('admin/frontend.general.all-rights-reserved') }}
            </p>
        </div>

        {{-- Form panel --}}
        <div class="flex w-full flex-col justify-center px-6 py-12 sm:px-12 lg:w-1/2 lg:px-20">
            <div class="mx-auto w-full max-w-md">

                <x-admin.brand class="mb-8 lg:hidden"
                               logo-class="h-11 w-11"
                               name-class="text-lg font-bold tracking-tight text-foreground" />

                <h1 class="text-2xl font-bold tracking-tight text-foreground">
                    {{ __('admin/frontend.auth.welcome-back') }}
                </h1>
                <p class="mt-2 text-sm text-muted-foreground">
                    {{ __('admin/frontend.auth.sign-in-subtitle') }}
                </p>

                <form method="POST" action="{{ route('admin.login.submit') }}" class="mt-8 space-y-5">
                    @csrf

                    <x-admin.ui.input
                        name="email"
                        type="email"
                        :label="__('admin/frontend.auth.email')"
                        icon="mail"
                        :required="true"
                        autocomplete="email"
                        autofocus
                        :placeholder="__('admin/frontend.auth.email-placeholder')" />

                    <x-admin.ui.input
                        name="password"
                        type="password"
                        :label="__('admin/frontend.auth.password')"
                        icon="lock"
                        :required="true"
                        autocomplete="current-password"
                        :placeholder="__('admin/frontend.auth.password-placeholder')">
                        <button type="button" data-password-toggle="#password"
                                class="absolute top-1/2 right-3.5 -translate-y-1/2 text-muted-foreground transition hover:text-foreground"
                                aria-label="{{ __('admin/frontend.auth.toggle-password') }}">
                            <x-admin.icon name="visibility" class="text-xl" />
                        </button>
                    </x-admin.ui.input>

                    <label class="flex cursor-pointer items-center gap-2.5 text-sm text-muted-foreground select-none">
                        <input type="checkbox" name="remember_me" value="1"
                               class="h-4 w-4 rounded border-border">
                        {{ __('admin/frontend.auth.remember-me') }}
                    </label>

                    <x-admin.ui.button type="submit" class="w-full">
                        <x-admin.icon name="login" class="text-lg" />
                        {{ __('admin/frontend.auth.sign-in') }}
                    </x-admin.ui.button>
                </form>

                <div class="my-7 flex items-center gap-4">
                    <span class="h-px flex-1 bg-border"></span>
                    <span class="text-xs font-medium tracking-wide text-muted-foreground uppercase">{{ __('admin/frontend.auth.or') }}</span>
                    <span class="h-px flex-1 bg-border"></span>
                </div>

                <a href="{{ route('admin.oauth-login', ['driver' => 'google']) }}"
                   class="flex w-full items-center justify-center gap-3 rounded-xl border border-border bg-surface px-4 py-2.5 text-sm font-semibold text-foreground transition hover:bg-muted">
                    <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" alt="" class="h-5 w-5">
                    {{ __('admin/frontend.auth.continue-with-google') }}
                </a>
            </div>
        </div>
    </div>
</x-admin.guest-layout>
