@props([
    'name',
    'label',
    'type' => 'text',
    'icon' => null,
    'autocomplete' => null,
    'required' => false,
    'autofocus' => false,
    'placeholder' => '',
    'value' => null,
    'hint' => null,
])

@php
    $hasError = $errors->has($name);
    $describedBy = collect([
        $hint ? $name.'-hint' : null,
        $hasError ? $name.'-error' : null,
    ])->filter()->implode(' ');
@endphp

{{-- Labelled input with an optional leading icon, a trailing action slot (e.g. a
     password toggle), a hint, and inline validation errors.

     The error is wired with aria-describedby and aria-invalid rather than colour
     alone, and it carries an icon — so the failure is conveyed three ways. --}}
<div>
    <label for="{{ $name }}" class="mb-1.5 block text-sm font-medium text-foreground">
        {{ $label }}
        @if ($required)
            <span class="text-danger" aria-hidden="true">*</span>
            <span class="sr-only">({{ __('frontend.general.required') }})</span>
        @endif
    </label>

    <div class="relative">
        @if ($icon)
            <span class="material-symbols-outlined pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-[1.2rem] text-foreground-muted/70" aria-hidden="true">{{ $icon }}</span>
        @endif

        <input
            id="{{ $name }}"
            name="{{ $name }}"
            type="{{ $type }}"
            value="{{ $type === 'password' ? '' : old($name, $value) }}"
            @required($required)
            @if ($autofocus) autofocus @endif
            @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            @if ($hasError) aria-invalid="true" @endif
            placeholder="{{ $placeholder }}"
            {{ $attributes->class([
                'block w-full rounded-xl border bg-surface/70 py-2.5 text-foreground shadow-sm backdrop-blur-md transition duration-(--motion-feedback) ease-(--ease-snap) placeholder:text-foreground-muted/55 focus:outline-none focus:ring-2',
                'border-danger/60 focus:border-danger focus:ring-danger/30' => $hasError,
                'border-border-strong focus:border-primary/60 focus:ring-ring/40' => ! $hasError,
                'pl-11' => $icon,
                'pl-4' => ! $icon,
                'pr-11' => $slot->isNotEmpty(),
                'pr-4' => $slot->isEmpty(),
            ]) }}>

        {{ $slot }}
    </div>

    @if ($hint && ! $hasError)
        <p id="{{ $name }}-hint" class="mt-1.5 text-xs text-foreground-muted/80">{{ $hint }}</p>
    @endif

    @error($name)
        <p id="{{ $name }}-error" class="mt-1.5 flex items-center gap-1 text-xs text-danger">
            <span class="material-symbols-outlined text-[0.95rem]" aria-hidden="true">error</span>{{ $message }}
        </p>
    @enderror
</div>
