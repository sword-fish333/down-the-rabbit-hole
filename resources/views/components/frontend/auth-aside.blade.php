{{-- Desktop-only brand showcase for the auth split layout. The noir warren of
     light is in custom.css (.dth-auth-aside); kept out of the mobile flow. --}}
<aside class="dth-auth-aside relative hidden overflow-hidden border-r border-border/60 lg:flex lg:w-1/2 lg:flex-col lg:justify-between lg:p-12">
    <div class="pointer-events-none absolute -bottom-10 -left-10 h-56 w-56 rounded-full bg-clay-500/12 blur-3xl"></div>

    <a href="{{ route('home') }}" class="group relative flex items-center gap-2.5">
        <img src="{{ loadFiles('images/logos/main_logo.png') }}" alt="{{ config('app.name') }}"
             width="40" height="40" decoding="async"
             class="dth-logo h-10 w-10 rounded-full object-contain ring-1 ring-border/60">
        <span class="font-display text-lg font-semibold tracking-tight text-foreground">{{ config('app.name') }}</span>
    </a>

    <div class="relative max-w-md">
        <h2 class="text-balance font-display text-4xl font-semibold leading-[1.1] tracking-tight text-foreground">
            {{ __('frontend.auth.brand-headline') }}
        </h2>
        <p class="mt-4 text-pretty text-base text-foreground-muted">{{ __('frontend.auth.brand-subline') }}</p>

        <ul class="mt-10 space-y-4">
            @foreach (['depth' => 'stairs_2', 'prove' => 'verified', 'streak' => 'local_fire_department'] as $key => $icon)
                <li class="flex items-center gap-3 text-sm text-foreground/85">
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-primary/12 text-primary ring-1 ring-primary/20">
                        <span class="material-symbols-outlined text-[1.15rem]">{{ $icon }}</span>
                    </span>
                    {{ __('frontend.auth.feature-'.$key) }}
                </li>
            @endforeach
        </ul>
    </div>

    <p class="relative text-xs text-foreground-muted/60">
        &copy; {{ date('Y') }} {{ config('app.name') }}. {{ __('frontend.footer.rights') }}
    </p>
</aside>
