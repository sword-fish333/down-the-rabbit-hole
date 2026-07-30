@props([
    'class' => '',
])
{{--
    Dark/light toggle. A real `role="switch"` with `aria-checked`, so screen
    readers and keyboards get platform behaviour; public/js/frontend/app.js
    mirrors the state and persists it, and the visuals live in
    public/css/frontend/custom.css (.theme-switch).

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
    {{ $attributes->merge(['class' => 'theme-switch '.$class]) }}
>
    <span class="theme-switch-icon theme-switch-icon-sun" aria-hidden="true">
        <span class="material-symbols-outlined">light_mode</span>
    </span>
    <span class="theme-switch-icon theme-switch-icon-moon" aria-hidden="true">
        <span class="material-symbols-outlined">dark_mode</span>
    </span>
    <span class="theme-switch-thumb" aria-hidden="true"></span>
</button>
