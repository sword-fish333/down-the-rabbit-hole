@props([
    'name',          // a Google Material Symbols glyph name, e.g. "dashboard"
    'filled' => false,
])
{{--
    Material Symbols icon. Size follows font-size, so pass any text-* utility
    (e.g. class="text-lg") to resize. Webfont is loaded in admin/partials/head.
--}}
<span {{ $attributes->class(['material-symbols-outlined', 'is-filled' => $filled]) }} aria-hidden="true">{{ $name }}</span>
