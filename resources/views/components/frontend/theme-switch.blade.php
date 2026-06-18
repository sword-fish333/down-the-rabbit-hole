@props([
    'class' => '',
])
{{--
    Premium dark/light mode toggle.
    State is mirrored via aria-checked + data-theme by public/js/actions.js -> ThemeManager.
    Same markup serves desktop and mobile; CSS in public/css/custom.css handles the visuals.
--}}
<button
    type="button"
    role="switch"
    aria-checked="false"
    aria-label="{{ __('frontend.navbar.theme-toggle') }}"
    title="{{ __('frontend.navbar.theme-toggle') }}"
    data-theme-switch
    {{ $attributes->merge(['class' => 'theme-switch ' . $class]) }}
>
    <span class="theme-switch-icon theme-switch-icon-sun" aria-hidden="true">
        <span class="material-symbols-outlined">light_mode</span>
    </span>
    <span class="theme-switch-icon theme-switch-icon-moon" aria-hidden="true">
        <span class="material-symbols-outlined">dark_mode</span>
    </span>
    <span class="theme-switch-thumb" aria-hidden="true"></span>
</button>
