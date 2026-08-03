@props(['peripheral' => false])

{{-- Frontend footer. Quiet and wet-matte — the bold colour lives up in the hero.

     It carries the one thing a deep-work product owes its users and rarely
     says out loud: a statement of what it will not do to their attention. That
     is a positioning claim, so it sits in the footer as a promise, not in a
     modal as a boast. --}}
<footer @class([
    'relative z-10 border-t border-border/70 bg-surface/40',
    'dth-peripheral' => $peripheral,
])>
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-4">
            {{-- Brand + promise --}}
            <div class="lg:col-span-2">
                <a href="{{ route('home') }}" class="group inline-flex items-center gap-2.5">
                    <img src="{{ loadFiles('images/logos/main_logo.png') }}" alt=""
                         width="30" height="30" loading="lazy" decoding="async"
                         class="dth-logo h-[1.85rem] w-[1.85rem] shrink-0 rounded-full object-contain">
                    <span class="font-display text-base font-semibold tracking-tight text-foreground">{{ config('app.name') }}</span>
                </a>
                <p class="mt-3 max-w-sm text-pretty text-sm leading-relaxed text-foreground-muted">
                    {{ __('frontend.footer.blurb') }}
                </p>

                <ul class="mt-5 space-y-1.5">
                    @foreach (['no-feed', 'no-streak-guilt', 'depth-not-time'] as $promise)
                        <li class="flex items-start gap-2 text-xs text-foreground-muted/85">
                            <span class="material-symbols-outlined mt-px text-[0.95rem] text-success" aria-hidden="true">check_small</span>
                            {{ __('frontend.footer.promises.'.$promise) }}
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Learn --}}
            <nav aria-labelledby="dth-footer-learn">
                <h2 id="dth-footer-learn" class="dth-coord mb-3">{{ __('frontend.footer.learn') }}</h2>
                <ul class="space-y-2.5 text-sm">
                    <li>
                        <a href="{{ route('home') }}" class="text-foreground-muted transition duration-(--motion-feedback) hover:text-foreground">
                            {{ __('frontend.footer.start-descent') }}
                        </a>
                    </li>
                    @auth
                        <li>
                            <a href="{{ route('subjects.index') }}" class="text-foreground-muted transition duration-(--motion-feedback) hover:text-foreground">
                                {{ __('frontend.footer.your-subjects') }}
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('profile.index') }}" class="text-foreground-muted transition duration-(--motion-feedback) hover:text-foreground">
                                {{ __('frontend.footer.your-record') }}
                            </a>
                        </li>
                    @else
                        <li>
                            <a href="{{ route('register') }}" class="text-foreground-muted transition duration-(--motion-feedback) hover:text-foreground">
                                {{ __('frontend.footer.create-account') }}
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('login') }}" class="text-foreground-muted transition duration-(--motion-feedback) hover:text-foreground">
                                {{ __('frontend.footer.sign-in') }}
                            </a>
                        </li>
                    @endauth
                </ul>
            </nav>

            {{-- How it works — the mechanic, stated plainly. --}}
            <div>
                <h2 class="dth-coord mb-3">{{ __('frontend.footer.how') }}</h2>
                <ol class="space-y-2.5 text-sm text-foreground-muted">
                    @foreach (['name', 'prove', 'descend'] as $index => $step)
                        <li class="flex items-start gap-2.5">
                            <span class="mt-0.5 font-mono text-xs text-primary/80" aria-hidden="true">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</span>
                            {{ __('frontend.footer.steps.'.$step) }}
                        </li>
                    @endforeach
                </ol>
            </div>
        </div>

        <div class="mt-10 flex flex-col items-start justify-between gap-3 border-t border-border/60 pt-6 sm:flex-row sm:items-center">
            <p class="text-xs text-foreground-muted/70">
                &copy; {{ date('Y') }} {{ config('app.name') }}. {{ __('frontend.footer.rights') }}
            </p>
            <p class="dth-coord">{{ __('frontend.footer.tagline') }}</p>
        </div>
    </div>
</footer>
