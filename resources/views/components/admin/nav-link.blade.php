@props([
    'href',
    'icon' => null,          // Material Symbols glyph name
    'active' => false,
    'label' => null,         // tooltip shown when the rail is collapsed
])

<a href="{{ $href }}"
   @if ($active) aria-current="page" @endif
   @if ($label) title="{{ $label }}" @endif
   {{ $attributes->class([
       'group relative flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm font-medium transition nav-collapsed:lg:justify-center nav-collapsed:lg:px-0',
       'bg-primary/10 text-primary' => $active,
       'text-muted-foreground hover:bg-muted hover:text-foreground' => ! $active,
   ]) }}>
    {{-- Active indicator rail (hidden when the sidebar is collapsed) --}}
    <span @class([
        'absolute left-0 top-1/2 h-5 w-1 -translate-y-1/2 rounded-r-full bg-primary transition nav-collapsed:lg:hidden',
        'opacity-100' => $active,
        'opacity-0' => ! $active,
    ])></span>

    @if ($icon)
        <x-admin.icon :name="$icon" :filled="$active"
                      class="shrink-0 {{ $active ? 'text-primary' : 'text-muted-foreground group-hover:text-foreground' }}" />
    @endif
    <span class="nav-collapsed:lg:hidden">{{ $slot }}</span>
</a>
