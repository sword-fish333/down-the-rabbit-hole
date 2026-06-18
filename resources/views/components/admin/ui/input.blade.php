@props([
    'name',
    'label' => null,
    'type' => 'text',
    'value' => null,
    'icon' => null,          // Material Symbols glyph name
    'required' => false,
])

<div>
    @if ($label)
        <label for="{{ $name }}" class="mb-1.5 block text-sm font-medium text-foreground">
            {{ $label }}@if ($required)<span class="text-danger"> *</span>@endif
        </label>
    @endif

    <div class="relative">
        @if ($icon)
            <x-admin.icon :name="$icon"
                          class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-muted-foreground text-xl" />
        @endif

        <input
            id="{{ $name }}"
            name="{{ $name }}"
            type="{{ $type }}"
            value="{{ old($name, $value) }}"
            @if ($required) required @endif
            {{ $attributes->class([
                'w-full rounded-xl border bg-surface px-4 py-2.5 text-sm text-foreground shadow-sm transition placeholder:text-muted-foreground focus:ring-2 focus:outline-none',
                'pl-11' => $icon,
                'border-danger/60 focus:border-danger focus:ring-danger/25' => $errors->has($name),
                'border-border focus:border-primary focus:ring-primary/25' => ! $errors->has($name),
            ]) }}>

        {{ $slot }}
    </div>

    @error($name)
        <p class="mt-1.5 text-xs text-danger">{{ $message }}</p>
    @enderror
</div>
