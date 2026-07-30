@props([
    'name',
    'label' => null,
    'options' => [],       // value => label
    'value' => null,
    'placeholder' => null,
    'required' => false,
    'hint' => null,
])

<div>
    @if ($label)
        <label for="{{ $name }}" class="mb-1.5 block text-sm font-medium text-foreground">
            {{ $label }}@if ($required)<span class="text-danger" aria-hidden="true"> *</span>@endif
        </label>
    @endif

    <select id="{{ $name }}" name="{{ $name }}" @required($required)
            @if ($errors->has($name)) aria-invalid="true" @endif
            {{ $attributes->class([
                'w-full rounded-xl border bg-surface px-4 py-2.5 text-sm text-foreground shadow-sm transition focus:ring-2 focus:outline-none',
                'border-danger/60 focus:border-danger focus:ring-danger/25' => $errors->has($name),
                'border-border focus:border-primary focus:ring-primary/25' => ! $errors->has($name),
            ]) }}>
        @if ($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif

        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected((string) old($name, $value) === (string) $optionValue)>
                {{ $optionLabel }}
            </option>
        @endforeach
    </select>

    @if ($hint && ! $errors->has($name))
        <p class="mt-1.5 text-xs text-muted-foreground">{{ $hint }}</p>
    @endif

    @error($name)
        <p class="mt-1.5 text-xs text-danger">{{ $message }}</p>
    @enderror
</div>
