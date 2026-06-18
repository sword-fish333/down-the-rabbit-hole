@props([
    'href',
    'icon' => null,
    'active' => false,
])

<a href="{{ $href }}"
   @if ($active) aria-current="page" @endif
   {{ $attributes->class([
       'group flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm font-medium transition',
       'bg-brand-50 text-brand-700 dark:bg-brand-600/15 dark:text-brand-300' => $active,
       'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white' => ! $active,
   ]) }}>
    @if ($icon)
        <i class="fa-solid {{ $icon }} w-5 text-center {{ $active ? 'text-brand-600 dark:text-brand-400' : 'text-zinc-400 group-hover:text-zinc-600 dark:group-hover:text-zinc-300' }}"></i>
    @endif
    <span>{{ $slot }}</span>
</a>
