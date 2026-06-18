@props(['class' => ''])
{{--
    Admin light/dark pill toggle. Reuses the .theme-switch styles in
    public/css/custom.css. public/js/admin/app.js handles the toggle
    (data-theme-toggle) and mirrors state via aria-checked (data-theme-switch).
--}}
<button
    type="button"
    role="switch"
    aria-checked="false"
    data-theme-toggle
    data-theme-switch
    aria-label="{{ __('admin/frontend.nav.toggle-theme') }}"
    title="{{ __('admin/frontend.nav.toggle-theme') }}"
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
