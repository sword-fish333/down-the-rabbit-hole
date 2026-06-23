@props([
    'name',
    'label',
    'type' => 'text',
    'icon' => null,
    'autocomplete' => null,
    'required' => false,
    'autofocus' => false,
    'placeholder' => '',
])

{{-- Labelled input with an optional leading icon, a trailing action slot
     (e.g. a password toggle), and inline validation errors. --}}
<div>
    <label for="{{ $name }}" class="mb-1.5 block text-sm font-medium text-foreground">{{ $label }}</label>

    <div class="relative">
        @if ($icon)
            <span class="material-symbols-outlined pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-[1.2rem] text-foreground-muted/70">{{ $icon }}</span>
        @endif

        <input
            id="{{ $name }}"
            name="{{ $name }}"
            type="{{ $type }}"
            value="{{ $type === 'password' ? '' : old($name) }}"
            @required($required)
            @if ($autofocus) autofocus @endif
            @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
            placeholder="{{ $placeholder }}"
            {{ $attributes->class([
                'block w-full rounded-xl border border-border-strong bg-surface/70 py-2.5 text-foreground placeholder:text-foreground-muted/55 shadow-sm backdrop-blur-md transition focus:border-primary/60 focus:outline-none focus:ring-2 focus:ring-ring/40',
                'pl-11' => $icon,
                'pl-4' => ! $icon,
                'pr-11' => $slot->isNotEmpty(),
                'pr-4' => $slot->isEmpty(),
            ]) }}>

        {{ $slot }}
    </div>

    @error($name)
        <p class="mt-1.5 flex items-center gap-1 text-xs text-danger">
            <span class="material-symbols-outlined text-[0.95rem]">error</span>{{ $message }}
        </p>
    @enderror
</div>
