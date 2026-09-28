{{--
    Dark/light toggle. A real `role="switch"` with `aria-checked`, so screen
    readers and keyboards get platform behaviour; public/js/frontend/app.js
    mirrors the state and persists it.

    One geometry, so the thumb always lands dead-centre on an icon: a 64×32
    track with a 3px inset on every side, the two 24px icon cells pushed to its
    ends, and a 24px thumb that starts on the first cell and travels exactly the
    distance to the second — 62px inside the border, less 2×3px inset, less the
    24px thumb, is translate-x-8. Change one of those numbers and the others
    have to follow.

    Position and colour key off `.dark` (the `dark:` variant), which is set
    before first paint — never off aria-checked, which only app.js sets, so the
    thumb would load on the wrong side and slide across.

    The frontend defaults to dark — the noir hero is the intended first
    impression — but the visitor's choice is remembered.
--}}
<button
    type="button"
    role="switch"
    aria-checked="false"
    aria-label="{{ __('frontend.navbar.theme-toggle') }}"
    title="{{ __('frontend.navbar.theme-toggle') }}"
    data-theme-switch
    {{ $attributes->class('relative inline-flex h-8 w-16 cursor-pointer items-center justify-between rounded-full border border-border bg-surface-muted px-0.75 inset-shadow-xs inset-shadow-ink-950/12 transition duration-(--motion-state) ease-(--ease-out) hover:border-primary/40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring') }}
>
    <span class="material-symbols-outlined z-10 grid size-6 place-items-center text-[1rem] text-sand-600 transition-colors duration-(--motion-state) ease-(--ease-out) dark:text-foreground-muted/45" aria-hidden="true">light_mode</span>
    <span class="material-symbols-outlined z-10 grid size-6 place-items-center text-[1rem] text-foreground-muted/45 transition-colors duration-(--motion-state) ease-(--ease-out) dark:text-wave-400" aria-hidden="true">dark_mode</span>
    <span class="pointer-events-none absolute left-0.75 top-1/2 size-6 -translate-y-1/2 rounded-full bg-surface shadow-sm shadow-ink-950/30 ring-1 ring-border/60 transition-[translate,box-shadow] duration-(--motion-state) ease-(--ease-out) dark:translate-x-8 dark:shadow-[0_0_12px_-2px_color-mix(in_oklch,var(--color-wave-500)_55%,transparent)]" aria-hidden="true"></span>
</button>
