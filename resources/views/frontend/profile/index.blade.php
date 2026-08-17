@php($activeTab = session('active_profile_tab', 'record'))

<x-frontend.layout :title="__('frontend.profile.title')" :active-tab="$activeTab" shell>
    <section class="mx-auto max-w-4xl px-4 py-10 sm:px-6 lg:px-8">
        <x-frontend.verify-banner />

        {{-- Identity header --}}
        <div class="flex flex-col items-start gap-5 sm:flex-row sm:items-center">
            <form method="POST" action="{{ route('profile.update-avatar') }}" enctype="multipart/form-data"
                  class="group relative shrink-0">
                @csrf
                <x-frontend.avatar :user="$user" size="h-20 w-20" text="text-xl" />

                {{-- The label IS the control; the input stays visually hidden but
                     focusable, so keyboard users reach it in the normal order. --}}
                <label for="profile_img"
                       class="absolute inset-0 grid cursor-pointer place-items-center rounded-full bg-ink-950/60 text-primary-foreground opacity-0 transition duration-(--motion-feedback) group-hover:opacity-100 has-[:focus-visible]:opacity-100 has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-ring">
                    <span class="material-symbols-outlined text-[1.2rem]" aria-hidden="true">photo_camera</span>
                    <span class="sr-only">{{ __('frontend.profile.change-photo') }}</span>
                    <input id="profile_img" name="profile_img" type="file" accept="image/*" class="sr-only"
                           onchange="this.form.requestSubmit()">
                </label>
            </form>

            <div class="min-w-0">
                <h1 class="truncate font-display text-2xl font-semibold tracking-tight text-foreground">{{ $user->fullName() }}</h1>
                <p class="truncate text-sm text-foreground-muted">{{ $user->email }}</p>
                <p class="mt-1.5 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-foreground-muted">
                    <span class="inline-flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[0.95rem] text-primary" aria-hidden="true">bolt</span>
                        {{ __('frontend.profile.xp', ['xp' => number_format($user->xp)]) }}
                    </span>
                    @if ($streak && $streak->current_count > 0)
                        <span class="inline-flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[0.95rem] text-accent" aria-hidden="true">local_fire_department</span>
                            {{ trans_choice('frontend.navbar.descent-days', $streak->current_count, ['count' => $streak->current_count]) }}
                        </span>
                    @endif
                </p>
            </div>
        </div>

        @error('profile_img')
            <p role="alert" class="mt-4 text-xs text-danger">{{ $message }}</p>
        @enderror

        {{-- Tabs. Real ARIA tablist; app.js only toggles hidden + aria-selected. --}}
        <div class="mt-9 border-b border-border">
            <div role="tablist" aria-label="{{ __('frontend.profile.tabs-label') }}" class="-mb-px flex gap-1 overflow-x-auto">
                @foreach (['record' => 'insights', 'profile' => 'badge', 'password' => 'lock'] as $tab => $icon)
                    <button type="button" role="tab" data-tab="{{ $tab }}"
                            id="tab-{{ $tab }}" aria-controls="panel-{{ $tab }}"
                            aria-selected="{{ $activeTab === $tab ? 'true' : 'false' }}"
                            tabindex="{{ $activeTab === $tab ? '0' : '-1' }}"
                            class="inline-flex shrink-0 items-center gap-2 border-b-2 px-4 py-2.5 text-sm font-medium transition duration-(--motion-feedback) aria-selected:border-primary aria-selected:text-primary [&:not([aria-selected=true])]:border-transparent [&:not([aria-selected=true])]:text-foreground-muted [&:not([aria-selected=true])]:hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                        <span class="material-symbols-outlined text-[1.1rem]" aria-hidden="true">{{ $icon }}</span>
                        {{ __('frontend.profile.tab-'.$tab) }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- ---- Record: the reason to open this page ------------------- --}}
        <div role="tabpanel" id="panel-record" data-panel="record" aria-labelledby="tab-record"
             @if ($activeTab !== 'record') hidden @endif class="pt-8">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <x-frontend.stat :label="__('frontend.profile.record.layers')" :value="$record['layers']"
                                 icon="stairs" tone="primary" :hint="__('frontend.profile.record.layers-hint')" />
                <x-frontend.stat :label="__('frontend.profile.record.mastered')" :value="$record['mastered']"
                                 icon="verified" tone="success" :hint="__('frontend.profile.record.mastered-hint')" />
                <x-frontend.stat :label="__('frontend.profile.record.deepest')" :value="$record['deepest']"
                                 icon="south_east" tone="primary" :hint="__('frontend.profile.record.deepest-hint')" />
                <x-frontend.stat :label="__('frontend.profile.record.subjects')" :value="$record['subjects']" icon="forum" tone="muted" />
                <x-frontend.stat :label="__('frontend.profile.record.surfaced')" :value="$record['surfaced']"
                                 icon="workspace_premium" tone="success" />
                <x-frontend.stat :label="__('frontend.profile.record.to-review')" :value="$record['to_review']"
                                 icon="history" tone="accent" :hint="__('frontend.profile.record.to-review-hint')" />
            </div>

            {{-- The one line worth screenshotting. Only shown once there is a
                 real dive behind it — a zero-layer "personal best" is a taunt. --}}
            @if ($deepestDive && $deepestDive->layersCleared() > 0)
                <a href="{{ route('subject.show', $deepestDive) }}"
                   class="dth-card group mt-6 flex items-center gap-3 rounded-2xl border border-accent/30 bg-accent/8 px-4 py-3 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                    <span class="material-symbols-outlined shrink-0 text-[1.2rem] text-accent" aria-hidden="true">military_tech</span>
                    <span class="min-w-0">
                        <span class="dth-coord block">{{ __('frontend.profile.record.deepest-dive') }}</span>
                        <span class="block truncate text-sm font-medium text-foreground">
                            {{ trans_choice('frontend.profile.record.deepest-dive-value', $deepestDive->layersCleared(), [
                                'subject' => $deepestDive->displayTitle(),
                                'count' => $deepestDive->layersCleared(),
                            ]) }}
                        </span>
                    </span>
                    <span class="dth-card-arrow material-symbols-outlined ml-auto shrink-0 text-[1.1rem] text-accent" aria-hidden="true">arrow_forward</span>
                </a>
            @endif

            {{-- ---- The boards, opt-in ----------------------------------- --}}
            {{-- Off by default, and the switch says exactly what saying yes
                 publishes. Their standings are shown either way, because "you
                 would be 4th" is a real invitation and an empty promise is not. --}}
            <div @class([
                'mt-6 rounded-2xl border p-4 sm:p-5',
                'border-primary/30 bg-primary/8' => $user->isRanked(),
                'border-border/70 bg-surface/40' => ! $user->isRanked(),
            ])>
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="min-w-0 max-w-lg">
                        <p class="flex items-center gap-2 font-display text-sm font-semibold text-foreground">
                            <span class="material-symbols-outlined text-[1.15rem] text-primary" aria-hidden="true">leaderboard</span>
                            {{ $user->isRanked() ? __('frontend.rankings.leave-title') : __('frontend.rankings.join-title') }}
                        </p>
                        <p class="mt-1.5 text-xs leading-relaxed text-foreground-muted">
                            {{ $user->isRanked() ? __('frontend.rankings.leave-body') : __('frontend.rankings.join-body') }}
                        </p>
                    </div>

                    <form method="POST" action="{{ route('profile.update-ranking') }}" class="shrink-0">
                        @csrf
                        <input type="hidden" name="ranked" value="{{ $user->isRanked() ? '0' : '1' }}">

                        <button type="submit" @class([
                            'inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold transition duration-(--motion-feedback) ease-(--ease-snap) focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring',
                            'border border-border-strong text-foreground-muted hover:border-danger/50 hover:text-foreground' => $user->isRanked(),
                            'bg-primary text-primary-foreground hover:bg-primary/90' => ! $user->isRanked(),
                        ])>
                            <span class="material-symbols-outlined text-[1.1rem]" aria-hidden="true">{{ $user->isRanked() ? 'visibility_off' : 'groups' }}</span>
                            {{ $user->isRanked() ? __('frontend.rankings.leave-cta') : __('frontend.rankings.join-cta') }}
                        </button>
                    </form>
                </div>

                @if ($standings->isNotEmpty())
                    <ul class="mt-4 flex flex-wrap gap-2 border-t border-border/50 pt-4">
                        @foreach ($standings as $standing)
                            <li>
                                <a href="{{ route('rankings.index', ['board' => $standing['board']]) }}"
                                   class="dth-card flex items-center gap-2 rounded-xl border border-border bg-surface/40 px-3 py-2 text-xs text-foreground-muted transition hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                    {{ __('frontend.rankings.board.'.$standing['board']) }}
                                    <x-frontend.rank-badge :rank="$standing['rank']" />
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif

                @if ($user->isRanked())
                    <a href="{{ route('learners.show', $user) }}"
                       class="dth-coord mt-4 inline-flex items-center gap-1.5 rounded-lg text-primary transition hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                        <span class="material-symbols-outlined text-[1rem]" aria-hidden="true">visibility</span>
                        {{ __('frontend.navbar.public-record') }}
                    </a>
                @endif
            </div>

            {{-- No time-on-site number and no badge wall. This is the whole
                 gamification surface, and it is all outcome-based. --}}
            <p class="mt-6 text-xs leading-relaxed text-foreground-muted/80">{{ __('frontend.profile.record.note') }}</p>
        </div>

        {{-- ---- Details ------------------------------------------------ --}}
        <div role="tabpanel" id="panel-profile" data-panel="profile" aria-labelledby="tab-profile"
             @if ($activeTab !== 'profile') hidden @endif class="max-w-xl pt-8">
            <h2 class="font-display text-base font-semibold text-foreground">{{ __('frontend.profile.details') }}</h2>
            <p class="mt-1 text-sm text-foreground-muted">{{ __('frontend.profile.details-hint') }}</p>

            <form method="POST" action="{{ route('profile.update-profile') }}" class="mt-6 space-y-5">
                @csrf
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-frontend.text-field name="first_name" :label="__('frontend.auth.first-name')"
                                           :value="$user->first_name" required autocomplete="given-name" />
                    <x-frontend.text-field name="last_name" :label="__('frontend.auth.last-name')"
                                           :value="$user->last_name" autocomplete="family-name" />
                </div>

                <x-frontend.text-field name="email" type="email" :label="__('frontend.auth.email')"
                                       :value="$user->email" required autocomplete="email"
                                       :hint="__('frontend.profile.email-change-hint')" />

                <x-frontend.text-field name="phone" type="tel" :label="__('frontend.profile.phone')"
                                       :value="$user->phone" autocomplete="tel" />

                <button type="submit"
                        class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                    {{ __('frontend.profile.save') }}
                </button>
            </form>
        </div>

        {{-- ---- Password ----------------------------------------------- --}}
        <div role="tabpanel" id="panel-password" data-panel="password" aria-labelledby="tab-password"
             @if ($activeTab !== 'password') hidden @endif class="max-w-xl pt-8">
            <h2 class="font-display text-base font-semibold text-foreground">{{ __('frontend.profile.change-password') }}</h2>
            <p class="mt-1 text-sm text-foreground-muted">{{ __('frontend.profile.change-password-hint') }}</p>

            <form method="POST" action="{{ route('profile.update-password') }}" class="mt-6 space-y-5">
                @csrf
                <x-frontend.text-field name="current_password" type="password" :label="__('frontend.profile.current-password')"
                                       icon="lock" required autocomplete="current-password" />
                <x-frontend.text-field name="password" type="password" :label="__('frontend.profile.new-password')"
                                       icon="key" required autocomplete="new-password"
                                       :hint="__('frontend.auth.password-hint')" />
                <x-frontend.text-field name="password_confirmation" type="password" :label="__('frontend.auth.password-confirm')"
                                       icon="key" required autocomplete="new-password" />

                <button type="submit"
                        class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                    {{ __('frontend.profile.update-password') }}
                </button>
            </form>
        </div>
    </section>
</x-frontend.layout>
