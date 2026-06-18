@props([
    'name',
    'label' => null,
    'type' => 'text',
    'value' => null,
    'icon' => null,
    'required' => false,
])

<div>
    @if ($label)
        <label for="{{ $name }}" class="mb-1.5 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
            {{ $label }}@if ($required)<span class="text-rose-500"> *</span>@endif
        </label>
    @endif

    <div class="relative">
        @if ($icon)
            <i class="fa-solid {{ $icon }} pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-zinc-400"></i>
        @endif

        <input
            id="{{ $name }}"
            name="{{ $name }}"
            type="{{ $type }}"
            value="{{ old($name, $value) }}"
            @if ($required) required @endif
            {{ $attributes->class([
                'w-full rounded-xl border bg-white px-4 py-2.5 text-sm text-zinc-900 shadow-sm transition placeholder:text-zinc-400 focus:ring-2 focus:outline-none dark:bg-zinc-900 dark:text-white',
                'pl-10' => $icon,
                'border-rose-400 focus:border-rose-500 focus:ring-rose-500/30' => $errors->has($name),
                'border-zinc-200 focus:border-brand-500 focus:ring-brand-500/30 dark:border-zinc-700' => ! $errors->has($name),
            ]) }}>

        {{ $slot }}
    </div>

    @error($name)
        <p class="mt-1.5 text-xs text-rose-500">{{ $message }}</p>
    @enderror
</div>
